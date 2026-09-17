<?php

use Illuminate\Support\Facades\Route;
use OiLab\OiLaravelChangelogs\Services\ChangeLogRepository;

beforeEach(function () {
    /*
     * The provider reads route.enabled once, when it boots, so the flag has to
     * be in place before the application is built rather than after.
     */
    $this->routesEnabled = false;
    $this->refreshApplication();
});

it('registers no route when the host wants to declare its own', function () {
    expect(Route::has('change-logs.index'))->toBeFalse()
        ->and(Route::has('change-logs.show'))->toBeFalse();

    $this->get('/change-logs')->assertNotFound();
});

it('still reads the journal, so a host controller has something to call', function () {
    $this->writeEntry('2026-09-12-an-entry');

    expect(app(ChangeLogRepository::class)->all())->toHaveCount(1);
});
