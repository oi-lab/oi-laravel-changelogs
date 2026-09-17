<?php

use Illuminate\Support\Facades\Route;
use OiLab\OiLaravelChangelogs\Http\Controllers\ChangeLogController;

/*
 * The journal, and one entry of it. Two GETs and nothing else: an entry is
 * written in the pull request that carries the fix, reviewed with it and
 * deployed with it.
 *
 * The slug is the file's own name, constrained to what a file name may hold
 * so nothing outside the change log directory can be asked for.
 */
Route::middleware((array) config('oi-laravel-changelogs.route.middleware', ['web']))
    ->prefix((string) config('oi-laravel-changelogs.route.prefix', 'change-logs'))
    ->name((string) config('oi-laravel-changelogs.route.name', 'change-logs.'))
    ->group(function (): void {
        Route::get('/', [ChangeLogController::class, 'index'])->name('index');

        Route::get('/{slug}', [ChangeLogController::class, 'show'])
            ->where('slug', '[a-z0-9\-]+')
            ->name('show');
    });
