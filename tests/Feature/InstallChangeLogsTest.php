<?php

use Illuminate\Support\Facades\File;
use Illuminate\Testing\PendingCommand;
use OiLab\OiLaravelChangelogs\OiLaravelChangelogs;

/**
 * The installer writes into the testbench application, which is a real
 * directory under vendor/: everything it creates is named here so the suite
 * leaves nothing behind.
 */
afterEach(function () {
    File::delete(config_path('oi-laravel-changelogs.php'));
    File::deleteDirectory(base_path(OiLaravelChangelogs::componentsPath()));
    File::deleteDirectory(base_path(OiLaravelChangelogs::pagesPath()));
    File::deleteDirectory(resource_path('js/layouts'));
    File::delete([
        resource_path('js/lib/change-log-typography.ts'),
        resource_path('js/lib/change-log-date.ts'),
    ]);
});

/**
 * Run the installer through its questions.
 *
 * @param  array<string, mixed>  $answers
 */
function install(array $answers = []): PendingCommand
{
    $answers = [
        'access' => 'Authenticated users only (adds the "auth" middleware)',
        'engine' => 'Server-side — Laravel converts the markdown to HTML (no npm dependency)',
        'ssr' => 'no',
        'typeset' => 'no',
        'shadcn' => 'no',
        'npm' => null,
        ...$answers,
    ];

    $command = test()->artisan('change-log:install', ['--force' => true])
        ->expectsConfirmation('Install these?', 'yes')
        ->expectsChoice('Who should be able to read the change logs?', $answers['access'], [
            'Public — anyone can read the journal',
            'Authenticated users only (adds the "auth" middleware)',
            'Restricted by a custom middleware',
        ])
        ->expectsChoice('Where should the markdown be converted?', $answers['engine'], [
            'Server-side — Laravel converts the markdown to HTML (no npm dependency)',
            'Client-side — the browser converts it (react-markdown + remark-gfm)',
        ])
        ->expectsConfirmation('Does your application render Inertia with SSR (resources/js/ssr.tsx)?', $answers['ssr'])
        ->expectsConfirmation('Apply Shadcn UI\'s "typeset" typography class to the entry content?', $answers['typeset']);

    /*
     * The npm question only comes up when the browser is doing the converting,
     * and it comes up before the shadcn one — the order is what Mockery is
     * checking.
     */
    if ($answers['npm'] !== null) {
        $command->expectsConfirmation(
            'The client-side renderer needs: react-markdown@^9.0.0 remark-gfm@^4.0.0. Install them now?',
            $answers['npm'],
        );
    }

    /*
     * And the shadcn question only comes up while one of those components is
     * still missing from the host application.
     */
    if ($answers['shadcn'] !== null) {
        $command->expectsConfirmation(
            'The screen draws with these Shadcn UI components: badge, button, empty, item, skeleton. Add them now?',
            $answers['shadcn'],
        );
    }

    return $command;
}

it('publishes the configuration, the directory and the screen', function () {
    install()->assertSuccessful();

    expect(File::exists(config_path('oi-laravel-changelogs.php')))->toBeTrue();

    $components = base_path(OiLaravelChangelogs::componentsPath());

    expect(File::exists($components.'/types.ts'))->toBeTrue()
        ->and(File::exists($components.'/change-log-row.tsx'))->toBeTrue()
        ->and(File::exists($components.'/change-log-detail.tsx'))->toBeTrue()
        ->and(File::exists($components.'/change-log-pagination.tsx'))->toBeTrue()
        ->and(File::exists($components.'/change-log-html-content.tsx'))->toBeTrue()
        ->and(File::exists(resource_path('js/lib/change-log-typography.ts')))->toBeTrue()
        ->and(File::exists(resource_path('js/lib/change-log-date.ts')))->toBeTrue()
        ->and(File::exists(resource_path('js/layouts/change-logs-layout.tsx')))->toBeTrue()
        ->and(File::exists(base_path(OiLaravelChangelogs::pagesPath().'/index.tsx')))->toBeTrue();
});

it('writes an example entry the checker is happy with', function () {
    install()->assertSuccessful();

    $entries = File::files(OiLaravelChangelogs::path());

    expect($entries)->toHaveCount(1)
        ->and($entries[0]->getFilename())->toEndWith('-an-example-entry.md');

    $this->withoutMockingConsoleOutput();

    expect($this->artisan('change-log:check'))->toBe(0);
});

it('records the chosen middleware in the published config', function () {
    install(['access' => 'Public — anyone can read the journal'])->assertSuccessful();

    expect(File::get(config_path('oi-laravel-changelogs.php')))
        ->toContain("'middleware' => ['web'],");
});

it('leaves the client renderer out when Laravel does the converting', function () {
    install()->assertSuccessful();

    $components = base_path(OiLaravelChangelogs::componentsPath());

    expect(File::exists($components.'/change-log-markdown-content.tsx'))->toBeFalse();

    expect(File::get($components.'/change-log-detail.tsx'))
        ->not->toContain('ChangeLogMarkdownContent')
        ->toContain('<ChangeLogHtmlContent html={entry.html ?? \'\'} />');
});

it('installs the client renderer when the browser does the converting', function () {
    File::put(base_path('package.json'), json_encode(['dependencies' => ['react' => '^19.0.0']]));

    install([
        'engine' => 'Client-side — the browser converts it (react-markdown + remark-gfm)',
        'npm' => 'no',
    ])->assertSuccessful();

    File::delete(base_path('package.json'));

    $components = base_path(OiLaravelChangelogs::componentsPath());

    expect(File::exists($components.'/change-log-markdown-content.tsx'))->toBeTrue()
        ->and(File::get($components.'/change-log-detail.tsx'))
        ->toContain('ChangeLogMarkdownContent');
});

it('rewrites the import alias when the components live elsewhere', function () {
    config()->set('oi-laravel-changelogs.components_path', 'resources/js/features/journal');

    install()->assertSuccessful();

    expect(File::get(base_path('resources/js/features/journal/change-log-detail.tsx')))
        ->toContain("from '@/features/journal/types'")
        ->not->toContain('@/components/change-logs');

    expect(File::get(base_path(OiLaravelChangelogs::pagesPath().'/index.tsx')))
        ->toContain("from '@/features/journal/change-log-row'");

    File::deleteDirectory(base_path('resources/js/features'));
});

it('swaps the typography class for the typeset one when asked', function () {
    install(['typeset' => 'yes'])->assertSuccessful();

    expect(File::get(resource_path('js/lib/change-log-typography.ts')))
        ->toContain("CHANGE_LOG_TYPOGRAPHY_CLASS = 'typeset'");
});

it('says there is nothing to do when everything is already installed', function () {
    /*
     * The shadcn step is the only one that looks outside what this package
     * writes, so a bare testbench application always has it left to do.
     */
    File::ensureDirectoryExists(resource_path('js/components/ui'));

    foreach (['badge', 'button', 'empty', 'item', 'skeleton'] as $component) {
        File::put(resource_path("js/components/ui/{$component}.tsx"), '// stub');
    }

    install(['shadcn' => null])->assertSuccessful();

    $this->artisan('change-log:install')
        ->expectsOutputToContain('Everything is already installed')
        ->assertSuccessful();

    File::deleteDirectory(resource_path('js/components'));
});
