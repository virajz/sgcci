<?php

declare(strict_types=1);

use App\Models\Exhibition;

test('it closes registration for exhibitions that have ended', function () {
    $ended = Exhibition::factory()->create([
        'end_date' => now()->subDay(),
        'registration_closed' => false,
    ]);

    $this->artisan('exhibitions:close-ended-registrations')->assertSuccessful();

    expect($ended->fresh()->registration_closed)->toBeTrue();
});

test('it closes registration for exhibitions ending today', function () {
    $today = Exhibition::factory()->create([
        'end_date' => now(),
        'registration_closed' => false,
    ]);

    $this->artisan('exhibitions:close-ended-registrations')->assertSuccessful();

    expect($today->fresh()->registration_closed)->toBeTrue();
});

test('it leaves future exhibitions open', function () {
    $future = Exhibition::factory()->create([
        'end_date' => now()->addWeek(),
        'registration_closed' => false,
    ]);

    $this->artisan('exhibitions:close-ended-registrations')->assertSuccessful();

    expect($future->fresh()->registration_closed)->toBeFalse();
});

test('dry run reports without persisting changes', function () {
    $ended = Exhibition::factory()->create([
        'end_date' => now()->subDay(),
        'registration_closed' => false,
    ]);

    $this->artisan('exhibitions:close-ended-registrations --dry-run')->assertSuccessful();

    expect($ended->fresh()->registration_closed)->toBeFalse();
});
