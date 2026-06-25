<?php

declare(strict_types=1);

use App\Models\Exhibition;
use App\Models\ExhibitionVisitor;
use App\Services\QrCodeService;

it('maps the registration source to a pass label', function (?string $source, ?string $expected) {
    $visitor = ExhibitionVisitor::factory()->make(['source' => $source]);

    expect($visitor->passLabel())->toBe($expected);
})->with([
    'committee' => ['committee_member', 'Managing Committee'],
    'member' => ['member', 'SGCCI Member'],
    'walk-in' => ['front_desk', null],
    'none' => [null, null],
]);

it('draws the label onto the pass so the image differs from the unlabelled one', function () {
    $exhibition = Exhibition::factory()->create();
    $service = app(QrCodeService::class);

    $plain = $service->generateVisitorPassImage('https://example.test/scan', 'Asha Patel', $exhibition);
    $labelled = $service->generateVisitorPassImage('https://example.test/scan', 'Asha Patel', $exhibition, 'Managing Committee');

    expect(substr($labelled, 0, 2))->toBe("\xFF\xD8") // valid JPEG
        ->and($labelled)->not->toBe($plain);
});
