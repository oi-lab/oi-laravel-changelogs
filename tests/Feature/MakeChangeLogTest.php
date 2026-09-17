<?php

use Illuminate\Support\Facades\File;
use Symfony\Component\Yaml\Yaml;

function frontmatterOf(string $file): array
{
    return Yaml::parse(preg_split('/^---$/m', File::get($file), 3)[1]);
}

it('opens an entry named after its date and its title', function () {
    $this->artisan('change-log:make', [
        'title' => 'The dashboard came back blank',
        '--type' => 'improvement',
        '--tag' => ['Console', 'Teams'],
        '--commit' => ['9a9ab33'],
        '--date' => '2026-09-12',
    ])->assertSuccessful();

    $file = $this->path().'/2026-09-12-the-dashboard-came-back-blank.md';

    expect(File::exists($file))->toBeTrue();

    expect(frontmatterOf($file))->toMatchArray([
        'title' => 'The dashboard came back blank',
        'type' => 'improvement',
        'tags' => ['console', 'teams'],
        'commits' => ['9a9ab33'],
    ]);
});

it('leaves placeholders saying what is still missing', function () {
    $this->artisan('change-log:make', ['title' => 'Something broke', '--date' => '2026-09-12'])
        ->assertSuccessful();

    $body = File::get($this->path().'/2026-09-12-something-broke.md');

    expect($body)->toContain('TODO')
        ->and($body)->toContain('## Changes')
        ->and($body)->toMatch('/^-\s+TODO/m');
});

it('writes the placeholders in the language of the application', function () {
    app()->setLocale('fr');

    $this->artisan('change-log:make', ['title' => 'Something broke', '--date' => '2026-09-12'])
        ->assertSuccessful();

    expect(File::get($this->path().'/2026-09-12-something-broke.md'))
        ->toContain('## Corrections')
        ->toContain("ce que voyait la personne devant l'écran");
});

it('creates the directory when there is none yet', function () {
    File::deleteDirectory($this->path());

    $this->artisan('change-log:make', ['title' => 'Something broke', '--date' => '2026-09-12'])
        ->assertSuccessful();

    expect(File::isDirectory($this->path()))->toBeTrue();
});

it('refuses a type that is neither a repair nor an improvement', function () {
    $this->artisan('change-log:make', ['title' => 'Something broke', '--type' => 'refactor'])
        ->expectsOutputToContain('Unknown type "refactor"')
        ->assertFailed();

    expect(File::files($this->path()))->toBeEmpty();
});

it('refuses a date that is not one', function () {
    $this->artisan('change-log:make', ['title' => 'Something broke', '--date' => 'tuesday-ish'])
        ->assertFailed();

    expect(File::files($this->path()))->toBeEmpty();
});

it('will not overwrite an entry unless told to', function () {
    $this->writeEntry('2026-09-12-something-broke', ['title' => 'The first one']);

    $this->artisan('change-log:make', ['title' => 'Something broke', '--date' => '2026-09-12'])
        ->assertFailed();

    expect(frontmatterOf($this->path().'/2026-09-12-something-broke.md')['title'])->toBe('The first one');

    $this->artisan('change-log:make', ['title' => 'Something broke', '--date' => '2026-09-12', '--force' => true])
        ->assertSuccessful();

    expect(frontmatterOf($this->path().'/2026-09-12-something-broke.md')['title'])->toBe('Something broke');
});

it('cuts a long file name on a word rather than through one', function () {
    $this->artisan('change-log:make', [
        'title' => 'The printed dossier came out with the pages of the annexes in the wrong order entirely',
        '--date' => '2026-09-12',
    ])->assertSuccessful();

    $name = basename(File::files($this->path())[0]->getFilename(), '.md');
    $slug = substr($name, 11);

    expect(strlen($slug))->toBeLessThanOrEqual(60)
        ->and($slug)->not->toEndWith('-')
        ->and($name)->toStartWith('2026-09-12-the-printed-dossier-came-out-with-the-pages-of-the');
});

it('quotes a title yaml would otherwise choke on', function () {
    $this->artisan('change-log:make', ['title' => 'Impression: les annexes étaient inversées', '--date' => '2026-09-12'])
        ->assertSuccessful();

    expect(frontmatterOf(File::files($this->path())[0]->getPathname())['title'])
        ->toBe('Impression: les annexes étaient inversées');
});

it('drops a tag named twice', function () {
    $this->artisan('change-log:make', [
        'title' => 'Something broke',
        '--date' => '2026-09-12',
        '--tag' => ['console', 'Console', ' console '],
    ])->assertSuccessful();

    expect(frontmatterOf($this->path().'/2026-09-12-something-broke.md')['tags'])->toBe(['console']);
});
