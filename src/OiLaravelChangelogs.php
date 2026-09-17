<?php

namespace OiLab\OiLaravelChangelogs;

/**
 * Everything the package reads from its configuration, in one place.
 *
 * The paths are the reason this exists. Three of them are configurable and
 * two of them derive from a third — the Inertia component name is the pages
 * directory with "resources/js/pages/" cut off it — so a host moving the
 * screen into its console space edits one config key and nothing else.
 */
class OiLaravelChangelogs
{
    /**
     * Where the entries are kept, as an absolute path.
     */
    public static function path(): string
    {
        return base_path(static::relativePath());
    }

    /**
     * Where the entries are kept, relative to the base path.
     */
    public static function relativePath(): string
    {
        return static::normalize((string) config('oi-laravel-changelogs.changelog_path', 'resources/markdown/change-logs'));
    }

    /**
     * Where the published React components live, relative to the base path.
     */
    public static function componentsPath(): string
    {
        return static::normalize((string) config('oi-laravel-changelogs.components_path', 'resources/js/components/change-logs'));
    }

    /**
     * Where the published Inertia page lives, relative to the base path.
     */
    public static function pagesPath(): string
    {
        return static::normalize((string) config('oi-laravel-changelogs.pages_path', 'resources/js/pages/change-logs'));
    }

    /**
     * The "@/" import alias the published components answer to.
     *
     * Anything outside resources/js/ has no alias to speak of, so the path is
     * handed back as-is and the installer says so out loud.
     */
    public static function componentsAlias(): string
    {
        $path = static::componentsPath();

        return str_starts_with($path, 'resources/js/')
            ? '@/'.substr($path, strlen('resources/js/'))
            : $path;
    }

    /**
     * The Inertia component the controller renders.
     *
     * Inertia names a page relative to resources/js/pages, which is exactly
     * what the configured pages path holds past that prefix.
     */
    public static function component(string $page = 'index'): string
    {
        $path = static::pagesPath();

        $relative = str_starts_with($path, 'resources/js/pages/')
            ? substr($path, strlen('resources/js/pages/'))
            : $path;

        return trim($relative, '/').'/'.$page;
    }

    /**
     * A route name of the package, prefixed as the configuration asks.
     */
    public static function routeName(string $suffix): string
    {
        return (string) config('oi-laravel-changelogs.route.name', 'change-logs.').$suffix;
    }

    /**
     * How many entries one page of the list carries.
     */
    public static function perPage(): int
    {
        return max(1, (int) config('oi-laravel-changelogs.per_page', 20));
    }

    /**
     * The language the entries are written in.
     */
    public static function locale(): string
    {
        $locale = config('oi-laravel-changelogs.entries.locale');

        return is_string($locale) && $locale !== '' ? $locale : (string) app()->getLocale();
    }

    /**
     * The bounds the opening paragraph of an entry is held to, in words.
     *
     * @return array{min: int, max: int}
     */
    public static function summaryWords(): array
    {
        return [
            'min' => (int) config('oi-laravel-changelogs.entries.summary_words.min', 30),
            'max' => (int) config('oi-laravel-changelogs.entries.summary_words.max', 75),
        ];
    }

    /**
     * How many subjects one entry may be filed under.
     */
    public static function maxTags(): int
    {
        return max(1, (int) config('oi-laravel-changelogs.entries.max_tags', 5));
    }

    /**
     * Whether the markdown is converted to HTML before it leaves the server.
     */
    public static function rendersOnServer(): bool
    {
        return config('oi-laravel-changelogs.rendering.markdown_engine', 'server') !== 'client';
    }

    /**
     * A configured path, with its separators and its edges tidied.
     */
    private static function normalize(string $path): string
    {
        return trim(str_replace('\\', '/', $path), '/');
    }
}
