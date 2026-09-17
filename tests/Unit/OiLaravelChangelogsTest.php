<?php

use OiLab\OiLaravelChangelogs\OiLaravelChangelogs;

it('derives the inertia component from the pages path', function (string $pagesPath, string $component) {
    config()->set('oi-laravel-changelogs.pages_path', $pagesPath);

    expect(OiLaravelChangelogs::component())->toBe($component);
})->with([
    'default' => ['resources/js/pages/change-logs', 'change-logs/index'],
    'nested in a console space' => ['resources/js/pages/console/change-logs', 'console/change-logs/index'],
    'trailing slash' => ['resources/js/pages/change-logs/', 'change-logs/index'],
    'outside the pages directory' => ['resources/js/screens/change-logs', 'resources/js/screens/change-logs/index'],
]);

it('derives the import alias from the components path', function (string $componentsPath, string $alias) {
    config()->set('oi-laravel-changelogs.components_path', $componentsPath);

    expect(OiLaravelChangelogs::componentsAlias())->toBe($alias);
})->with([
    'default' => ['resources/js/components/change-logs', '@/components/change-logs'],
    'elsewhere under js' => ['resources/js/features/journal', '@/features/journal'],
    'outside resources/js' => ['modules/journal', 'modules/journal'],
]);

it('falls back to the application locale when none is configured', function () {
    config()->set('oi-laravel-changelogs.entries.locale', null);
    app()->setLocale('fr');

    expect(OiLaravelChangelogs::locale())->toBe('fr');

    config()->set('oi-laravel-changelogs.entries.locale', 'nl');

    expect(OiLaravelChangelogs::locale())->toBe('nl');
});

it('never returns a page size below one', function () {
    config()->set('oi-laravel-changelogs.per_page', 0);

    expect(OiLaravelChangelogs::perPage())->toBe(1);
});

it('renders on the server unless the client engine is configured', function () {
    expect(OiLaravelChangelogs::rendersOnServer())->toBeTrue();

    config()->set('oi-laravel-changelogs.rendering.markdown_engine', 'client');

    expect(OiLaravelChangelogs::rendersOnServer())->toBeFalse();
});
