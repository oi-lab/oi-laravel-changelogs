<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;

/**
 * The journal, as it is served. Inertia is asked for its json shape rather
 * than its html one: what matters here is the props the screen is handed,
 * and a root blade view is the host application's business.
 */
function visitScreen(string $url): TestResponse
{
    return test()->get($url, ['X-Inertia' => 'true', 'X-Inertia-Version' => '']);
}

it('registers its two routes', function () {
    expect(Route::has('change-logs.index'))->toBeTrue()
        ->and(Route::has('change-logs.show'))->toBeTrue();
});

it('opens the journal on the newest entry', function () {
    $this->writeEntry('2026-09-10-older');
    $this->writeEntry('2026-09-12-newest', ['title' => 'The newest one']);

    $page = visitScreen('/change-logs')->assertOk()->json();

    expect($page['component'])->toBe('change-logs/index')
        ->and($page['props']['entry']['slug'])->toBe('2026-09-12-newest')
        ->and($page['props']['entry']['title'])->toBe('The newest one')
        ->and($page['props']['entry']['html'])->toContain('<h2>Changes</h2>')
        ->and($page['props']['items']['data'])->toHaveCount(2)
        ->and($page['props']['baseUrl'])->toBe('/change-logs')
        ->and($page['props']['previous'])->toBeNull()
        ->and($page['props']['next']['slug'])->toBe('2026-09-10-older');
});

it('hands a row everything the list column draws', function () {
    $this->writeEntry('2026-09-12-an-entry', ['tags' => ['console', 'teams'], 'commits' => ['9a9ab33']]);

    $row = visitScreen('/change-logs')->json('props.items.data.0');

    expect($row)->toHaveKeys(['slug', 'title', 'date', 'type', 'typeLabel', 'tags', 'commits', 'summary'])
        ->and($row['type'])->toBe('fix')
        ->and($row['typeLabel'])->toBe('Fix')
        ->and($row['tags'])->toBe(['console', 'teams'])
        ->and($row)->not->toHaveKey('html');
});

it('opens on the page the entry sits on rather than on the first one', function () {
    config()->set('oi-laravel-changelogs.per_page', 2);

    foreach (range(10, 14) as $day) {
        $this->writeEntry("2026-09-{$day}-entry");
    }

    $page = visitScreen('/change-logs/2026-09-10-entry')->assertOk()->json();

    expect($page['props']['entry']['slug'])->toBe('2026-09-10-entry')
        ->and($page['props']['items']['current_page'])->toBe(3);
});

it('paginates the list without following the entry into the url', function () {
    config()->set('oi-laravel-changelogs.per_page', 2);

    foreach (range(10, 14) as $day) {
        $this->writeEntry("2026-09-{$day}-entry");
    }

    $items = visitScreen('/change-logs?page=2')->json('props.items');

    expect($items['current_page'])->toBe(2)
        ->and($items['data'])->toHaveCount(2)
        ->and($items['next_page_url'])->toContain('/change-logs?page=3');
});

it('answers a fetch with the entry alone', function () {
    $this->writeEntry('2026-09-10-older');
    $this->writeEntry('2026-09-12-newest');

    $answer = $this->getJson('/change-logs/2026-09-10-older')->assertOk()->json();

    expect(array_keys($answer))->toBe(['entry', 'previous', 'next'])
        ->and($answer['entry']['slug'])->toBe('2026-09-10-older')
        ->and($answer['entry']['html'])->toContain('<li>')
        ->and($answer['previous']['slug'])->toBe('2026-09-12-newest')
        ->and($answer['next'])->toBeNull();
});

it('404s on a slug no file carries', function () {
    $this->writeEntry('2026-09-12-an-entry');

    $this->getJson('/change-logs/2026-09-13-nothing')->assertNotFound();
});

it('refuses a slug that could walk out of the directory', function () {
    $this->get('/change-logs/../../.env')->assertNotFound();
});

it('draws an empty journal without an entry', function () {
    $page = visitScreen('/change-logs')->assertOk()->json();

    expect($page['props']['entry'])->toBeNull()
        ->and($page['props']['items']['data'])->toBe([])
        ->and($page['props']['previous'])->toBeNull()
        ->and($page['props']['next'])->toBeNull();
});

it('renders the component the pages path names', function () {
    config()->set('oi-laravel-changelogs.pages_path', 'resources/js/pages/console/change-logs');
    $this->writeEntry('2026-09-12-an-entry');

    expect(visitScreen('/change-logs')->json('component'))->toBe('console/change-logs/index');
});

it('sends the markdown instead of the html when the browser converts it', function () {
    config()->set('oi-laravel-changelogs.rendering.markdown_engine', 'client');
    $this->writeEntry('2026-09-12-an-entry');

    $entry = visitScreen('/change-logs')->json('props.entry');

    expect($entry['html'])->toBeNull()
        ->and($entry['markdown'])->toContain('## Changes');
});
