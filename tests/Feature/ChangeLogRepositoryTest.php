<?php

use Illuminate\Support\Facades\File;
use OiLab\OiLaravelChangelogs\Data\ChangeLogEntryData;
use OiLab\OiLaravelChangelogs\Enums\ChangeLogType;
use OiLab\OiLaravelChangelogs\Services\ChangeLogEntry;
use OiLab\OiLaravelChangelogs\Services\ChangeLogRepository;

function repository(): ChangeLogRepository
{
    return app(ChangeLogRepository::class);
}

it('reads an entry out of its file', function () {
    $this->writeEntry('2026-09-12-the-dashboard-came-back-blank', [
        'title' => 'The dashboard came back blank',
        'type' => 'improvement',
        'tags' => ['console', 'équipes'],
        'commits' => ['9a9ab33', '1c0ffee'],
    ]);

    $entry = repository()->all()->sole();

    expect($entry)->toBeInstanceOf(ChangeLogEntry::class)
        ->and($entry->slug)->toBe('2026-09-12-the-dashboard-came-back-blank')
        ->and($entry->title)->toBe('The dashboard came back blank')
        ->and($entry->date->toDateString())->toBe('2026-09-12')
        ->and($entry->type)->toBe(ChangeLogType::Improvement)
        ->and($entry->tags)->toBe(['console', 'équipes'])
        ->and($entry->commits)->toBe(['9a9ab33', '1c0ffee'])
        ->and($entry->summary)->toStartWith('The dashboard came back blank for anyone')
        ->and($entry->summary)->not->toContain("\n");
});

it('reads an unquoted date, which yaml hands back as a timestamp', function () {
    $this->writeEntry('2026-09-12-an-entry');

    expect(repository()->all()->sole()->date->toDateString())->toBe('2026-09-12');
});

it('orders the entries newest first, breaking ties on the slug', function () {
    $this->writeEntry('2026-09-10-older');
    $this->writeEntry('2026-09-12-zebra');
    $this->writeEntry('2026-09-12-alpha');

    expect(repository()->all()->pluck('slug')->all())->toBe([
        '2026-09-12-zebra',
        '2026-09-12-alpha',
        '2026-09-10-older',
    ]);
});

it('skips a file it cannot read rather than throwing on it', function (string $name, string $contents) {
    $this->writeEntry('2026-09-12-a-good-one');
    File::put($this->path().'/'.$name.'.md', $contents);

    expect(repository()->all()->pluck('slug')->all())->toBe(['2026-09-12-a-good-one']);
})->with([
    'no frontmatter' => ['2026-09-13-naked', "Just a paragraph.\n"],
    'unclosed frontmatter' => ['2026-09-13-unclosed', "---\ntitle: Nope\n"],
    'broken yaml' => ['2026-09-13-broken', "---\ntitle: [unclosed\n---\n\nBody.\n"],
    'unknown type' => ['2026-09-13-unknown', "---\ntitle: Nope\ndate: 2026-09-13\ntype: refactor\n---\n\nBody.\n"],
    'no title' => ['2026-09-13-untitled', "---\ndate: 2026-09-13\ntype: fix\n---\n\nBody.\n"],
    'unreadable date' => ['2026-09-13-undated', "---\ntitle: Nope\ndate: not-a-date\ntype: fix\n---\n\nBody.\n"],
]);

it('ignores anything that is not markdown', function () {
    $this->writeEntry('2026-09-12-an-entry');
    File::put($this->path().'/notes.txt', 'Not an entry.');

    expect(repository()->all())->toHaveCount(1);
});

it('reads an empty journal as an empty collection', function () {
    File::deleteDirectory($this->path());

    expect(repository()->all())->toBeEmpty();
});

it('leaves the summary empty when the body opens on a heading', function () {
    $this->writeEntry('2026-09-12-headed', body: "## Changes\n\n- Something.\n");

    expect(repository()->all()->sole()->summary)->toBe('');
});

it('pages the list', function () {
    foreach (range(10, 14) as $day) {
        $this->writeEntry("2026-09-{$day}-entry");
    }

    $page = repository()->paginate(2, 2, 'http://localhost/change-logs');

    expect($page->total())->toBe(5)
        ->and($page->lastPage())->toBe(3)
        ->and($page->items())->toHaveCount(2)
        ->and($page->items()[0]->slug)->toBe('2026-09-12-entry');
});

it('finds an entry by its slug, and nothing by a slug it does not hold', function () {
    $this->writeEntry('2026-09-12-an-entry');

    expect(repository()->find('2026-09-12-an-entry'))->not->toBeNull()
        ->and(repository()->find('2026-09-12-another-entry'))->toBeNull();
});

it('walks the list rather than the calendar', function () {
    $this->writeEntry('2026-09-10-oldest');
    $this->writeEntry('2026-09-11-middle');
    $this->writeEntry('2026-09-12-newest');

    $adjacent = repository()->adjacent('2026-09-11-middle');

    expect($adjacent['previous']->slug)->toBe('2026-09-12-newest')
        ->and($adjacent['next']->slug)->toBe('2026-09-10-oldest');

    expect(repository()->adjacent('2026-09-12-newest')['previous'])->toBeNull()
        ->and(repository()->adjacent('2026-09-10-oldest')['next'])->toBeNull()
        ->and(repository()->adjacent('unknown'))->toBe(['previous' => null, 'next' => null]);
});

it('says which page of the list an entry sits on', function () {
    foreach (range(10, 14) as $day) {
        $this->writeEntry("2026-09-{$day}-entry");
    }

    $repository = repository();

    expect($repository->pageOf($repository->find('2026-09-14-entry'), 2))->toBe(1)
        ->and($repository->pageOf($repository->find('2026-09-12-entry'), 2))->toBe(2)
        ->and($repository->pageOf($repository->find('2026-09-10-entry'), 2))->toBe(3)
        ->and($repository->pageOf(null, 2))->toBe(1);
});

it('converts the markdown on the server by default', function () {
    $this->writeEntry('2026-09-12-an-entry');

    $detail = repository()->detail(repository()->all()->sole());

    expect($detail)->toBeInstanceOf(ChangeLogEntryData::class)
        ->and($detail->html)->toContain('<h2>Changes</h2>')
        ->and($detail->html)->toContain('<li>')
        ->and($detail->markdown)->toBeNull();
});

it('sends the raw markdown when the browser is doing the converting', function () {
    config()->set('oi-laravel-changelogs.rendering.markdown_engine', 'client');
    $this->writeEntry('2026-09-12-an-entry');

    $detail = repository()->detail(repository()->all()->sole());

    expect($detail->markdown)->toContain('## Changes')
        ->and($detail->html)->toBeNull();
});

it('escapes the html an entry carries', function () {
    $this->writeEntry('2026-09-12-an-entry', body: "A paragraph with <script>alert('x')</script> in it.\n\n- One change.\n");

    expect(repository()->detail(repository()->all()->sole())->html)
        ->not->toContain('<script>')
        ->toContain('&lt;script&gt;');
});

it('rebuilds when a file is added to the directory', function () {
    $this->writeEntry('2026-09-12-first');

    expect(repository()->all())->toHaveCount(1);

    $this->writeEntry('2026-09-13-second');

    expect(repository()->all())->toHaveCount(2);
});

it('rebuilds when a file leaves the directory', function () {
    $this->writeEntry('2026-09-12-first');
    $second = $this->writeEntry('2026-09-13-second');

    expect(repository()->all())->toHaveCount(2);

    File::delete($second);

    expect(repository()->all())->toHaveCount(1);
});
