<?php

use OiLab\OiLaravelChangelogs\Enums\ChangeLogType;

it('has exactly two cases', function () {
    expect(ChangeLogType::values())->toBe(['fix', 'improvement']);
});

it('labels each case', function () {
    expect(ChangeLogType::Fix->label())->toBe('Fix')
        ->and(ChangeLogType::Improvement->label())->toBe('Improvement');
});

it('translates its labels', function () {
    app()->setLocale('fr');

    expect(ChangeLogType::Fix->label())->toBe('Correction')
        ->and(ChangeLogType::Improvement->label())->toBe('Amélioration');
});
