<?php

namespace OiLab\OiLaravelChangelogs;

use Illuminate\Support\ServiceProvider;
use OiLab\OiLaravelChangelogs\Console\Commands\CheckChangeLogs;
use OiLab\OiLaravelChangelogs\Console\Commands\InstallAiSkillCommand;
use OiLab\OiLaravelChangelogs\Console\Commands\InstallChangeLogs;
use OiLab\OiLaravelChangelogs\Console\Commands\MakeChangeLog;
use OiLab\OiLaravelChangelogs\Services\ChangeLogMarkdown;
use OiLab\OiLaravelChangelogs\Services\ChangeLogRepository;

class OiLaravelChangelogsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/oi-laravel-changelogs.php',
            'oi-laravel-changelogs'
        );

        /*
         * Both are singletons for the same reason: the markdown converter is
         * expensive to build and holds nothing request-specific, and the
         * repository holds nothing at all — its cache is the application's.
         * Under Octane the container survives the request, and that is exactly
         * what makes keeping them safe.
         */
        $this->app->singleton(ChangeLogMarkdown::class);

        $this->app->singleton(ChangeLogRepository::class, fn ($app): ChangeLogRepository => new ChangeLogRepository(
            $app->make(ChangeLogMarkdown::class),
        ));
    }

    public function boot(): void
    {
        $this->loadJsonTranslationsFrom(__DIR__.'/../lang');

        if (config('oi-laravel-changelogs.route.enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallChangeLogs::class,
                MakeChangeLog::class,
                CheckChangeLogs::class,
                InstallAiSkillCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/oi-laravel-changelogs.php' => config_path('oi-laravel-changelogs.php'),
            ], 'oi-laravel-changelogs-config');

            $this->publishes([
                __DIR__.'/../resources/stubs/ai-skill.md' => base_path('.claude/skills/oilab-laravel-changelogs/SKILL.md'),
            ], 'oi-laravel-changelogs-skill');
        }
    }
}
