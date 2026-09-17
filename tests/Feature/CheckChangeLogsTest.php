<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

/**
 * Run the checker and hand back what it printed.
 *
 * The console output is not mocked: `components->bulletList` writes the whole
 * list in a single call, so the framework's substring expectations can only
 * ever match one bullet of it — and every interesting assertion here is about
 * the bullets.
 *
 * @return array{code: int, output: string}
 */
function check(): array
{
    test()->withoutMockingConsoleOutput();

    return ['code' => test()->artisan('change-log:check'), 'output' => Artisan::output()];
}

it('passes on a well formed journal', function () {
    $this->writeEntry('2026-09-12-an-entry');
    $this->writeEntry('2026-09-13-another-entry');

    ['code' => $code, 'output' => $output] = check();

    expect($code)->toBe(0)
        ->and($output)->toContain('2 change log entries, all well formed');
});

it('passes on an empty directory, and fails when there is none', function () {
    expect(check()['code'])->toBe(0);

    File::deleteDirectory($this->path());

    ['code' => $code, 'output' => $output] = check();

    expect($code)->toBe(1)
        ->and($output)->toContain('No change log directory');
});

it('prints the subjects in use and how many entries each holds', function () {
    $this->writeEntry('2026-09-12-one', ['tags' => ['console', 'teams']]);
    $this->writeEntry('2026-09-13-two', ['tags' => ['console']]);

    expect(check()['output'])
        ->toContain('console (2)')
        ->toContain('teams (1)');
});

it('names what is wrong with an entry', function (array $frontmatter, ?string $body, string $problem) {
    $this->writeEntry('2026-09-12-an-entry', $frontmatter, $body);

    ['code' => $code, 'output' => $output] = check();

    expect($code)->toBe(1)
        ->and($output)->toContain('2026-09-12-an-entry.md — '.$problem);
})->with([
    'no title' => [['title' => ''], null, 'no title'],
    'unknown type' => [['type' => 'refactor'], null, 'the type should be one of: fix, improvement'],
    'no tags' => [['tags' => []], null, 'no tags: name at least one subject'],
    'too many tags' => [['tags' => ['a', 'b', 'c', 'd', 'e', 'f']], null, 'more than 5 tags'],
    'a tag that is not one' => [['tags' => ['Console']], null, '"Console" is not a tag'],
    'a commit that is not one' => [['commits' => ['zzzzzzz']], null, '"zzzzzzz" is not a commit hash'],
    'a date the file name disagrees with' => [['date' => '2026-09-13'], null, 'the date does not match'],
    'a date that is not one' => [['date' => 'tuesday-ish'], null, 'the date is not a date'],
    'a body opening on a heading' => [[], "## Changes\n\n- One change.\n", 'no opening paragraph'],
    'a summary too short' => [[], "Too short.\n\n- One change.\n", 'the opening paragraph is 2 words, expected between 30 and 75'],
    'a placeholder left in' => [
        [],
        "It broke, and the reason it broke was a query reading the team out of a session nobody had opened yet, which is a sentence just long enough to clear the word count this row is not about. TODO finish this.\n\n- One change.\n",
        'a TODO placeholder is still in the text',
    ],
    'no list of changes' => [
        [],
        "It broke, and the reason it broke was a query reading the team out of a session nobody had opened yet, which is a sentence just long enough to clear the word count this row is not about.\n\nAnd a second paragraph.\n",
        'no list of what was changed',
    ],
]);

it('names a file whose name is not the format', function () {
    File::put($this->path().'/Not-A-Name.md', File::get($this->writeEntry('2026-09-12-an-entry')));

    expect(check()['output'])->toContain('the file name should read YYYY-MM-DD-slug.md');
});

it('names a file it could not parse at all', function (string $contents, string $problem) {
    File::put($this->path().'/2026-09-12-broken.md', $contents);

    ['code' => $code, 'output' => $output] = check();

    expect($code)->toBe(1)->and($output)->toContain($problem);
})->with([
    'no frontmatter' => ["Just a paragraph.\n", 'no frontmatter: the file must open on a --- block'],
    'unclosed frontmatter' => ["---\ntitle: Nope\n", 'the frontmatter block is never closed'],
    'broken yaml' => ["---\ntitle: [unclosed\n---\n\nBody.\n", 'the frontmatter is not valid yaml'],
    'an empty frontmatter' => ["---\n\n---\n\nBody.\n", 'the frontmatter is not a map of keys'],
]);

it('holds the summary to the bounds the configuration sets', function () {
    config()->set('oi-laravel-changelogs.entries.summary_words', ['min' => 5, 'max' => 10]);

    $this->writeEntry('2026-09-12-an-entry', body: "Short enough for these bounds.\n\n- One change.\n");

    expect(check()['code'])->toBe(0);
});

it('holds the tags to the count the configuration sets', function () {
    config()->set('oi-laravel-changelogs.entries.max_tags', 2);

    $this->writeEntry('2026-09-12-an-entry', ['tags' => ['one', 'two', 'three']]);

    expect(check()['output'])->toContain('more than 2 tags');
});

it('counts every problem of every file', function () {
    $this->writeEntry('2026-09-12-one', ['title' => '', 'tags' => []]);
    $this->writeEntry('2026-09-13-two', ['type' => 'refactor']);

    expect(check()['output'])->toContain('3 problem(s) in 2 entries');
});
