<?php

declare(strict_types=1);

use App\Models\Exhibition;
use App\Services\CurrentExhibition;

it('returns null when nothing is selected', function () {
    expect(CurrentExhibition::id())->toBeNull();
    expect(CurrentExhibition::model())->toBeNull();
    expect(CurrentExhibition::isSelected())->toBeFalse();
});

it('stores the selected exhibition in the session', function () {
    $exhibition = Exhibition::factory()->create();

    CurrentExhibition::set($exhibition->id);

    expect(CurrentExhibition::id())->toBe($exhibition->id);
    expect(CurrentExhibition::model()?->id)->toBe($exhibition->id);
    expect(CurrentExhibition::isSelected())->toBeTrue();
});

it('clears the selection', function () {
    $exhibition = Exhibition::factory()->create();
    CurrentExhibition::set($exhibition->id);

    CurrentExhibition::clear();

    expect(CurrentExhibition::id())->toBeNull();
    expect(CurrentExhibition::isSelected())->toBeFalse();
});

it('clears the session when the exhibition is missing', function () {
    $exhibition = Exhibition::factory()->create();
    CurrentExhibition::set($exhibition->id);
    $exhibition->delete();

    expect(CurrentExhibition::model())->toBeNull();
    expect(CurrentExhibition::id())->toBeNull();
});
