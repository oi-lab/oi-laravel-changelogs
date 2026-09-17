<?php

namespace OiLab\OiLaravelChangelogs\Tests;

use Illuminate\Support\Facades\File;
use Inertia\ServiceProvider as InertiaServiceProvider;
use OiLab\OiLaravelChangelogs\OiLaravelChangelogsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LaravelData\LaravelDataServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * Where the entries of the test journal are written.
     *
     * Under the testbench base path and named after the test run, so two
     * tests never read each other's entries and nothing survives the suite.
     */
    protected string $changeLogPath = 'tests-change-logs';

    /**
     * Whether the package registers its own routes.
     *
     * The provider reads the flag once, when it boots, so a test that wants
     * them gone sets this and calls refreshApplication().
     */
    protected bool $routesEnabled = true;

    protected function getPackageProviders($app): array
    {
        return [
            LaravelDataServiceProvider::class,
            InertiaServiceProvider::class,
            OiLaravelChangelogsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('oi-laravel-changelogs.changelog_path', $this->changeLogPath);
        $app['config']->set('oi-laravel-changelogs.route.enabled', $this->routesEnabled);
        $app['config']->set('cache.default', 'array');
    }

    protected function setUp(): void
    {
        parent::setUp();

        File::deleteDirectory($this->path());
        File::ensureDirectoryExists($this->path());
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->path());

        parent::tearDown();
    }

    /**
     * The absolute path of the test journal.
     */
    protected function path(): string
    {
        return base_path($this->changeLogPath);
    }

    /**
     * Write one entry, defaulting to a well formed one.
     *
     * @param  array<string, mixed>  $frontmatter
     */
    protected function writeEntry(string $name, array $frontmatter = [], ?string $body = null): string
    {
        $frontmatter = [
            'title' => 'Something was repaired',
            'date' => substr($name, 0, 10),
            'type' => 'fix',
            'tags' => ['console'],
            'commits' => ['9a9ab33'],
            ...$frontmatter,
        ];

        $lines = ['---'];

        foreach ($frontmatter as $key => $value) {
            if (is_array($value)) {
                $lines[] = $value === []
                    ? $key.': []'
                    : $key.":\n".implode("\n", array_map(static fn ($item): string => '    - '.$item, $value));

                continue;
            }

            $lines[] = $key.': '.$value;
        }

        $lines[] = '---';

        $file = $this->path().'/'.$name.'.md';

        File::put($file, implode("\n", $lines)."\n\n".($body ?? $this->body()));

        return $file;
    }

    /**
     * A body the checker is happy with: an opening paragraph inside the word
     * bounds, then a list of what changed.
     */
    protected function body(): string
    {
        return implode("\n", [
            'The dashboard came back blank for anyone who was not an administrator, '.
            'and the screen said nothing at all about why it had — no message, no empty '.
            'state, just a white column sitting under the filters nobody could use. '.
            'The query scoped its results before the team was resolved.',
            '',
            '## Changes',
            '',
            '- Resolved the team before the scope is applied.',
            '- Added a feature test covering a member of no team.',
            '',
        ]);
    }
}
