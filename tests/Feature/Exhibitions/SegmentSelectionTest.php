<?php

use App\Livewire\Exhibitions\VisitorsRegistration;
use App\Models\Exhibition;
use App\Models\ExhibitionVisitor;
use App\Models\Segment;
use App\Models\SubSegment;
use Database\Seeders\SegmentSeeder;
use Livewire\Livewire;

it('seeds 30 segments and 600+ sub-segments from the master CSV', function () {
    Segment::query()->delete();

    $this->seed(SegmentSeeder::class);

    expect(Segment::count())->toBe(30);
    expect(SubSegment::count())->toBeGreaterThan(600);

    $textile = Segment::where('name', 'Textile')->firstOrFail();
    expect($textile->subSegments()->count())->toBeGreaterThanOrEqual(50);
});

it('clears the sub-segment when the segment changes', function () {
    $this->seed(SegmentSeeder::class);

    $textile = Segment::where('name', 'Textile')->firstOrFail();
    $textileSub = $textile->subSegments()->first();
    $diamond = Segment::where('name', 'Diamond')->firstOrFail();

    $exhibition = Exhibition::factory()->create([
        'start_date' => '2027-01-01',
        'end_date' => '2027-01-05',
    ]);

    Livewire::test(VisitorsRegistration::class, ['exhibition' => $exhibition])
        ->set('segmentId', $textile->id)
        ->set('subSegmentId', $textileSub->id)
        ->set('segmentId', $diamond->id)
        ->assertSet('subSegmentId', null);
});

it('rejects a sub-segment that does not belong to the chosen segment', function () {
    $this->seed(SegmentSeeder::class);

    $textile = Segment::where('name', 'Textile')->firstOrFail();
    $diamond = Segment::where('name', 'Diamond')->firstOrFail();
    $diamondSub = $diamond->subSegments()->first();

    $exhibition = Exhibition::factory()->create([
        'start_date' => '2027-01-01',
        'end_date' => '2027-01-05',
    ]);

    Livewire::test(VisitorsRegistration::class, ['exhibition' => $exhibition])
        ->set('phoneNumber', '9876543210')
        ->set('name', 'Tester')
        ->set('state', 'Gujarat')
        ->set('city', 'Surat')
        ->set('segmentId', $textile->id)
        ->set('subSegmentId', $diamondSub->id)
        ->call('nextStep')
        ->assertHasErrors(['subSegmentId']);
});

it('saves segment and sub-segment names on the visitor record', function () {
    $this->seed(SegmentSeeder::class);

    $textile = Segment::where('name', 'Textile')->firstOrFail();
    $textileSub = $textile->subSegments()->first();

    $exhibition = Exhibition::factory()->create([
        'start_date' => '2027-01-01',
        'end_date' => '2027-01-05',
        'entry_type' => 'free',
    ]);

    Livewire::test(VisitorsRegistration::class, ['exhibition' => $exhibition])
        ->set('phoneNumber', '9876543210')
        ->set('name', 'Tester')
        ->set('state', 'Gujarat')
        ->set('city', 'Surat')
        ->set('segmentId', $textile->id)
        ->set('subSegmentId', $textileSub->id)
        ->call('register')
        ->assertHasNoErrors();

    $visitor = ExhibitionVisitor::where('phone_number', '9876543210')->firstOrFail();
    expect($visitor->business_segment)->toBe('Textile');
    expect($visitor->sub_business_segment)->toBe($textileSub->name);
});
