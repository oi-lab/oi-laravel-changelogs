<?php

namespace OiLab\OiLaravelChangelogs\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use OiLab\OiLaravelChangelogs\Enums\ChangeLogType;
use OiLab\OiLaravelChangelogs\OiLaravelChangelogs;
use OiLab\OiLaravelChangelogs\Services\ChangeLogEntry;
use OiLab\OiLaravelChangelogs\Services\ChangeLogRepository;
use SplFileInfo;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Read every entry of the journal and say what is wrong with it.
 *
 * The screen skips a malformed file rather than throwing on it — a journal
 * that 500s because somebody mistyped a date is worse than a journal missing
 * an entry — so something has to say the entry went missing. This does, and
 * it belongs in the test suite: a file that would silently disappear from the
 * screen fails the build instead.
 *
 * It also holds the format to its word count. Thirty words is a sentence that
 * says what broke; seventy-five is the point past which nobody reads the
 * column. The bound is the only thing keeping a short description from
 * drifting into a second copy of the commit message.
 */
class CheckChangeLogs extends Command
{
    protected $signature = 'change-log:check';

    protected $description = 'Check every change log entry against the format';

    public function handle(ChangeLogRepository $changeLogs): int
    {
        $path = $changeLogs->path();

        if (! File::isDirectory($path)) {
            $this->components->error('No change log directory at '.$path.'.');

            return static::FAILURE;
        }

        $files = collect(File::files($path))
            ->filter(static fn (SplFileInfo $file): bool => $file->getExtension() === 'md')
            ->values();

        $problems = $files
            ->flatMap(fn (SplFileInfo $file): array => array_map(
                static fn (string $problem): string => $file->getFilename().' — '.$problem,
                $this->problemsWith($file),
            ))
            ->all();

        if ($problems !== []) {
            $this->components->error(count($problems).' problem(s) in '.$files->count().' entries.');
            $this->components->bulletList($problems);

            return static::FAILURE;
        }

        $this->components->info($files->count().' change log entries, all well formed.');

        $this->reportVocabulary($changeLogs);

        return static::SUCCESS;
    }

    /**
     * Print the subjects in use, and how many entries each one holds.
     *
     * The one thing no rule can catch: two spellings of the same subject.
     * Nothing here is an error — but a tag sitting alone next to its plural or
     * its unaccented twin is a typo you can see at a glance, and a journal is
     * small enough for that to be the whole of the guard it needs.
     */
    private function reportVocabulary(ChangeLogRepository $changeLogs): void
    {
        $tags = $changeLogs->all()
            ->flatMap(static fn (ChangeLogEntry $entry): array => $entry->tags)
            ->countBy()
            ->sortKeys();

        if ($tags->isEmpty()) {
            return;
        }

        $this->components->bulletList($tags
            ->map(static fn (int $count, string $tag): string => $tag.' ('.$count.')')
            ->values()
            ->all());
    }

    /**
     * Everything wrong with one file.
     *
     * @return list<string>
     */
    private function problemsWith(SplFileInfo $file): array
    {
        $content = File::get($file->getPathname());
        $problems = [];

        if (! preg_match('/^\d{4}-\d{2}-\d{2}-[a-z0-9\-]+$/', $file->getBasename('.md'))) {
            $problems[] = 'the file name should read YYYY-MM-DD-slug.md, lowercase.';
        }

        if (! str_starts_with($content, '---')) {
            return [...$problems, 'no frontmatter: the file must open on a --- block.'];
        }

        $parts = preg_split('/^---$/m', $content, 3);

        if ($parts === false || count($parts) < 3) {
            return [...$problems, 'the frontmatter block is never closed.'];
        }

        try {
            $frontmatter = Yaml::parse($parts[1]);
        } catch (ParseException $exception) {
            return [...$problems, 'the frontmatter is not valid yaml: '.$exception->getMessage()];
        }

        if (! is_array($frontmatter)) {
            return [...$problems, 'the frontmatter is not a map of keys.'];
        }

        return [
            ...$problems,
            ...$this->problemsWithFrontmatter($frontmatter, $file),
            ...$this->problemsWithBody(trim($parts[2])),
        ];
    }

    /**
     * What the metadata is missing or gets wrong.
     *
     * @param  array<string, mixed>  $frontmatter
     * @return list<string>
     */
    private function problemsWithFrontmatter(array $frontmatter, SplFileInfo $file): array
    {
        $problems = [];

        if (trim((string) ($frontmatter['title'] ?? '')) === '') {
            $problems[] = 'no title.';
        }

        $date = $frontmatter['date'] ?? null;

        if ($date === null) {
            $problems[] = 'no date.';
        } else {
            $parsed = ChangeLogRepository::parseDate($date);

            if ($parsed === null) {
                $problems[] = 'the date is not a date: '.(is_scalar($date) ? (string) $date : gettype($date)).'.';
            } elseif (! str_starts_with($file->getBasename('.md'), $parsed->toDateString())) {
                $problems[] = 'the date does not match the one the file name opens with.';
            }
        }

        if (ChangeLogType::tryFrom((string) ($frontmatter['type'] ?? '')) === null) {
            $problems[] = 'the type should be one of: '.implode(', ', ChangeLogType::values()).'.';
        }

        $commits = $frontmatter['commits'] ?? null;

        if ($commits !== null && ! is_array($commits)) {
            $problems[] = 'commits should be a list, even with a single hash in it.';
        }

        foreach (is_array($commits) ? $commits : [] as $commit) {
            if (! preg_match('/^[0-9a-f]{7,40}$/', trim((string) $commit))) {
                $problems[] = '"'.(is_scalar($commit) ? (string) $commit : gettype($commit)).'" is not a commit hash.';
            }
        }

        return [...$problems, ...$this->problemsWithTags($frontmatter['tags'] ?? null)];
    }

    /**
     * What the tags of an entry get wrong.
     *
     * Lowercase and hyphenated, because a tag exists to be grouped on: a
     * journal holding "Keynote", "keynote" and "Keynotes" has three subjects
     * where it means one, and nothing warns anybody — the badge draws whatever
     * it is handed. Accents are allowed; a journal names its subjects in its
     * own language.
     *
     * At least one, and at most what the configuration allows. None makes the
     * field decorative; one too many is somebody describing the entry rather
     * than filing it.
     *
     * @return list<string>
     */
    private function problemsWithTags(mixed $tags): array
    {
        if (! is_array($tags) || $tags === []) {
            return ['no tags: name at least one subject this entry belongs to.'];
        }

        $max = OiLaravelChangelogs::maxTags();

        if (count($tags) > $max) {
            return ['more than '.$max.' tags: file the entry, do not describe it.'];
        }

        $problems = [];

        foreach ($tags as $tag) {
            $written = is_scalar($tag) ? trim((string) $tag) : gettype($tag);

            if (! preg_match('/^[\p{Ll}\p{N}]+(?:-[\p{Ll}\p{N}]+)*$/u', $written)) {
                $problems[] = '"'.$written.'" is not a tag: lowercase words joined by hyphens.';
            }
        }

        return $problems;
    }

    /**
     * What the entry itself is missing.
     *
     * @return list<string>
     */
    private function problemsWithBody(string $body): array
    {
        $problems = [];

        $summary = (string) Str::of($body)->before("\n\n")->trim();

        if ($summary === '' || str_starts_with($summary, '#')) {
            return ['no opening paragraph describing the problem.'];
        }

        $words = count(preg_split('/\s+/u', $summary, -1, PREG_SPLIT_NO_EMPTY) ?: []);
        ['min' => $min, 'max' => $max] = OiLaravelChangelogs::summaryWords();

        if ($words < $min || $words > $max) {
            $problems[] = sprintf('the opening paragraph is %d words, expected between %d and %d.', $words, $min, $max);
        }

        if (str_contains($body, 'TODO')) {
            $problems[] = 'a TODO placeholder is still in the text.';
        }

        if (! preg_match('/^-\s+\S/m', (string) Str::of($body)->after($summary))) {
            $problems[] = 'no list of what was changed under the description.';
        }

        return $problems;
    }
}
