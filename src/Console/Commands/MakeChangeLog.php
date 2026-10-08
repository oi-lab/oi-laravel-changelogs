<?php

namespace OiLab\OiLaravelChangelogs\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use OiLab\OiLaravelChangelogs\Enums\ChangeLogType;
use OiLab\OiLaravelChangelogs\OiLaravelChangelogs;
use OiLab\OiLaravelChangelogs\Services\ChangeLogRepository;
use Throwable;

/**
 * Open an entry of the journal, with its frontmatter already filled in.
 *
 * The commit is the point of it. An entry is written once the fix is
 * committed, so the hash exists by then — but it exists in the terminal, not
 * in the head of whoever is writing, and a journal whose `commits` key is
 * empty half the time is a journal nobody can trace back to a diff. Left to
 * itself the command reads HEAD, which is the commit that was just made.
 */
class MakeChangeLog extends Command
{
    protected $signature = 'change-log:make
        {title : What broke, or what got better, in one sentence}
        {--type=fix : fix or improvement}
        {--tag=* : The subjects this entry is filed under, lowercase and hyphenated}
        {--commit=* : The commits this entry describes (defaults to HEAD)}
        {--date= : The day it is published (defaults to today)}
        {--force : Overwrite an entry already carrying that name}';

    protected $description = 'Open a change log entry in the configured change log directory';

    public function handle(ChangeLogRepository $changeLogs): int
    {
        $type = ChangeLogType::tryFrom((string) $this->option('type'));

        if ($type === null) {
            $this->components->error(sprintf(
                'Unknown type "%s". Expected one of: %s.',
                (string) $this->option('type'),
                implode(', ', ChangeLogType::values()),
            ));

            return static::FAILURE;
        }

        $title = trim((string) $this->argument('title'));
        $date = $this->date();

        if ($date === null) {
            $this->components->error('The --date option must be a date, e.g. 2026-09-12.');

            return static::FAILURE;
        }

        $path = $changeLogs->path();

        File::ensureDirectoryExists($path);

        $file = $path.'/'.$date->toDateString().'-'.$this->slug($title).'.md';

        if (File::exists($file) && ! $this->option('force')) {
            $this->components->error(basename($file).' already exists. Pass --force to overwrite it.');

            return static::FAILURE;
        }

        File::put($file, $this->stub($title, $date, $type, $this->tags(), $this->commits()));

        $bounds = OiLaravelChangelogs::summaryWords();

        $this->components->info('Written '.Str::after($file, base_path().DIRECTORY_SEPARATOR));
        $this->components->bulletList([
            'Write it in '.OiLaravelChangelogs::locale().', in the past tense.',
            'Describe the problem in the opening paragraph, between '.$bounds['min'].' and '.$bounds['max'].' words.',
            'List what was actually changed under the heading below it.',
            'Name at least one tag, lowercase and hyphenated, so the entry can be filed.',
            'Run `php artisan change-log:check` before committing.',
        ]);

        return static::SUCCESS;
    }

    /**
     * The file name a title earns, cut on a word rather than through one.
     *
     * A long title truncated at sixty characters lands mid-word, and that is
     * the name the entry keeps for good — in the url as much as on disk.
     * Trimming back to the last hyphen costs a few characters and spares every
     * later reader the question of what the word was going to be.
     */
    private function slug(string $title): string
    {
        $slug = Str::slug($title);

        if (mb_strlen($slug) <= 60) {
            return $slug;
        }

        $cut = mb_substr($slug, 0, 60);
        $lastWord = mb_strrpos($cut, '-');

        return $lastWord === false ? $cut : mb_substr($cut, 0, $lastWord);
    }

    /**
     * The day the entry is published.
     */
    private function date(): ?CarbonImmutable
    {
        $option = $this->option('date');

        if ($option === null || $option === '') {
            return CarbonImmutable::today();
        }

        try {
            return CarbonImmutable::parse((string) $option);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * The subjects the entry is filed under.
     *
     * Left empty when none is named rather than guessed from the title: a tag
     * is what groups this entry with the next one on the same subject, and a
     * word picked out of a sentence groups nothing. `change-log:check` refuses
     * an entry with none, which is where the reminder lands.
     *
     * @return list<string>
     */
    private function tags(): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn (mixed $tag): string => mb_strtolower(trim((string) $tag)),
            (array) $this->option('tag'),
        ))));
    }

    /**
     * The commits the entry describes.
     *
     * HEAD when none is named, because the workflow is: commit the fix, then
     * write the entry, then commit the entry on its own. A repository git
     * cannot read — a deployed checkout, a fresh export — yields nothing
     * rather than an error: an entry without a hash is still an entry.
     *
     * @return list<string>
     */
    private function commits(): array
    {
        /** @var list<string> $named */
        $named = array_values(array_filter(array_map(
            static fn (mixed $commit): string => trim((string) $commit),
            (array) $this->option('commit'),
        )));

        if ($named !== []) {
            return $named;
        }

        $head = Process::path(base_path())->run('git rev-parse --short HEAD');

        return $head->successful() ? [trim($head->output())] : [];
    }

    /**
     * The file as it is opened: everything the format asks for, and two
     * placeholders saying what is missing.
     *
     * @param  list<string>  $tags
     * @param  list<string>  $commits
     */
    private function stub(string $title, CarbonImmutable $date, ChangeLogType $type, array $tags, array $commits): string
    {
        $frontmatter = [
            '---',
            'title: '.$this->yaml($title),
            'date: '.$date->toDateString(),
            'type: '.$type->value,
            ...$this->yamlList('tags', $tags),
            // Quoted: an unquoted short hash such as 677e661 reads back as a
            // float in scientific notation (INF), and 1234567 as an integer.
            ...$this->yamlList('commits', array_map(static fn (string $commit): string => '"'.$commit.'"', $commits)),
            '---',
        ];

        return implode("\n", $frontmatter)."\n\n".implode("\n", [
            'TODO: '.__('describe the problem — what the person in front of the screen saw, then why it happened.'),
            '',
            '## '.__('Changes'),
            '',
            '- TODO: '.__('what was changed, one bullet per change, in the past tense.'),
            '',
        ]);
    }

    /**
     * A yaml key and the list under it, or the key and an empty list.
     *
     * @param  list<string>  $values
     * @return list<string>
     */
    private function yamlList(string $key, array $values): array
    {
        if ($values === []) {
            return [$key.': []'];
        }

        return [$key.':', ...array_map(static fn (string $value): string => '    - '.$value, $values)];
    }

    /**
     * Quote a title for yaml when it holds anything that would break it.
     */
    private function yaml(string $value): string
    {
        if (! preg_match('/^[^"\'{}\[\],&*#?|<>=!%@`:\-][^:#]*$/u', $value)) {
            return '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $value).'"';
        }

        return $value;
    }
}
