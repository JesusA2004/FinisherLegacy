<?php

use App\Models\PlateTemplate;
use Database\Seeders\PlateTemplateSeeder;

beforeEach(function () {
    $this->seed(PlateTemplateSeeder::class);
});

test('seeded plate template names do not hardcode the version', function () {
    $names = PlateTemplate::query()->pluck('name');

    expect($names)->toHaveCount(3);

    foreach ($names as $name) {
        expect($name)->not->toMatch('/\bV\d+\b/');
    }
});

test('seeded plate templates each have exactly one published V1 version', function () {
    PlateTemplate::query()->with('versions')->get()->each(function (PlateTemplate $template) {
        expect($template->versions)->toHaveCount(1);
        expect($template->versions->first()->version)->toBe(1);
    });
});
