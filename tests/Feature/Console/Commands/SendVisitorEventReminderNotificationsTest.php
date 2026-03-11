<?php

declare(strict_types=1);

use App\Jobs\SendWhatsAppCampaign;
use App\Models\Exhibition;
use App\Models\ExhibitionVisitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
});

it('sends reminders to confirmed visitors of upcoming exhibitions', function () {
    $exhibition = Exhibition::factory()->create([
        'start_date' => now()->addDays(3)->toDateString(),
        'end_date' => now()->addDays(5)->toDateString(),
    ]);

    $visitor = ExhibitionVisitor::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => 'confirmed',
        'name' => 'John Doe',
        'phone_number' => '9876543210',
    ]);

    $this->artisan('visitors:send-event-reminders')->assertSuccessful();

    Queue::assertPushed(SendWhatsAppCampaign::class, function ($job) use ($visitor, $exhibition) {
        $expectedImageUrl = config('app.url').'/'.$exhibition->slug.'/visitor-pass/'.$visitor->registration_code.'/image';

        return $job->campaignName === 'autoremainder'
            && $job->phoneNumber === $visitor->phone_number
            && $job->templateParams[0] === 'John'
            && $job->templateParams[1] === '3'
            && $job->templateParams[2] === $exhibition->title
            && $job->templateParams[3] === $visitor->registration_code
            && $job->media['url'] === $expectedImageUrl
            && $job->media['filename'] === 'visitor_pass_'.$visitor->registration_code;
    });
});

it('does not send reminders for exhibitions starting more than 3 days away', function () {
    $exhibition = Exhibition::factory()->create([
        'start_date' => now()->addDays(4)->toDateString(),
        'end_date' => now()->addDays(6)->toDateString(),
    ]);

    ExhibitionVisitor::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => 'confirmed',
    ]);

    $this->artisan('visitors:send-event-reminders')->assertSuccessful();

    Queue::assertNothingPushed();
});

it('skips non-confirmed visitors', function () {
    $exhibition = Exhibition::factory()->create([
        'start_date' => now()->addDays(2)->toDateString(),
        'end_date' => now()->addDays(4)->toDateString(),
    ]);

    ExhibitionVisitor::factory()->paymentPending()->create([
        'exhibition_id' => $exhibition->id,
    ]);

    $this->artisan('visitors:send-event-reminders')->assertSuccessful();

    Queue::assertNothingPushed();
});

it('does not send reminders for exhibitions that have already started', function () {
    $exhibition = Exhibition::factory()->create([
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => now()->addDays(2)->toDateString(),
    ]);

    ExhibitionVisitor::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => 'confirmed',
    ]);

    $this->artisan('visitors:send-event-reminders')->assertSuccessful();

    Queue::assertNothingPushed();
});

it('does nothing when there are no upcoming exhibitions', function () {
    $this->artisan('visitors:send-event-reminders')
        ->assertSuccessful()
        ->expectsOutputToContain('No upcoming exhibitions found.');

    Queue::assertNothingPushed();
});

it('does not send in dry run mode', function () {
    $exhibition = Exhibition::factory()->create([
        'start_date' => now()->addDays(5)->toDateString(),
        'end_date' => now()->addDays(7)->toDateString(),
    ]);

    ExhibitionVisitor::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => 'confirmed',
    ]);

    $this->artisan('visitors:send-event-reminders --dry-run')->assertSuccessful();

    Queue::assertNothingPushed();
});

it('only sends to the specified phone number when --phone is given', function () {
    $exhibition = Exhibition::factory()->create([
        'start_date' => now()->addDays(2)->toDateString(),
        'end_date' => now()->addDays(4)->toDateString(),
    ]);

    $targetVisitor = ExhibitionVisitor::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => 'confirmed',
        'phone_number' => '9876543210',
    ]);

    ExhibitionVisitor::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => 'confirmed',
        'phone_number' => '9999999999',
    ]);

    $this->artisan('visitors:send-event-reminders --phone=9876543210')->assertSuccessful();

    Queue::assertPushed(SendWhatsAppCampaign::class, 1);
    Queue::assertPushed(SendWhatsAppCampaign::class, fn ($job) => $job->phoneNumber === '9876543210');
});

it('uses Guest as first name fallback when visitor name is empty', function () {
    $exhibition = Exhibition::factory()->create([
        'start_date' => now()->addDays(1)->toDateString(),
        'end_date' => now()->addDays(3)->toDateString(),
    ]);

    ExhibitionVisitor::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => 'confirmed',
        'name' => '',
    ]);

    $this->artisan('visitors:send-event-reminders')->assertSuccessful();

    Queue::assertPushed(SendWhatsAppCampaign::class, function ($job) {
        return $job->templateParams[0] === 'Guest';
    });
});
