<?php

declare(strict_types=1);

use App\Jobs\SendWhatsAppCampaign;
use App\Livewire\Admin\CommitteeMembers\Index;
use App\Models\CommitteeMember;
use App\Models\Exhibition;
use App\Models\ExhibitionVisitor;
use App\Models\User;
use App\Services\CurrentExhibition;
use App\VisitorRegistrationStatus;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    Queue::fake();
    config(['services.whatsapp.enabled' => true]);

    $this->exhibition = Exhibition::factory()->create();
    CurrentExhibition::set($this->exhibition->id);
    $this->admin = User::factory()->admin()->create();
});

it('registers a committee member and sends the pass over WhatsApp', function () {
    $member = CommitteeMember::factory()->create(['name' => 'Asha Patel', 'mobile' => '9876543210']);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('sendWhatsApp', $member->id);

    $visitor = ExhibitionVisitor::where('phone_number', '9876543210')->first();

    expect($visitor)->not->toBeNull()
        ->and($visitor->exhibition_id)->toBe($this->exhibition->id)
        ->and($visitor->name)->toBe('Asha Patel')
        ->and($visitor->status)->toBe(VisitorRegistrationStatus::Confirmed)
        ->and($visitor->source)->toBe('committee_member');

    Queue::assertPushed(SendWhatsAppCampaign::class, function ($job) {
        return $job->campaignName === 'Paidregistration1'
            && $job->phoneNumber === '9876543210'
            && $job->templateParams[6] === 'Free Entry';
    });
});

it('opens the confirmation modal naming the member', function () {
    $member = CommitteeMember::factory()->create(['name' => 'Asha Patel', 'mobile' => '9876543210']);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('confirmSend', $member->id)
        ->assertSet('showSendModal', true)
        ->assertSet('memberToSend', $member->id)
        ->assertSet('memberToSendName', 'Asha Patel');

    expect(ExhibitionVisitor::count())->toBe(0);
    Queue::assertNothingPushed();
});

it('does not open the confirmation modal when no exhibition is selected', function () {
    CurrentExhibition::clear();
    $member = CommitteeMember::factory()->create(['mobile' => '9876543210']);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('confirmSend', $member->id)
        ->assertSet('showSendModal', false);
});

it('skips a committee member without a mobile number', function () {
    $member = CommitteeMember::factory()->withoutMobile()->create();

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('sendWhatsApp', $member->id);

    expect(ExhibitionVisitor::count())->toBe(0);
    Queue::assertNothingPushed();
});

it('reuses an existing registration instead of creating a duplicate', function () {
    $member = CommitteeMember::factory()->create(['mobile' => '9876543210']);
    ExhibitionVisitor::factory()->create([
        'exhibition_id' => $this->exhibition->id,
        'phone_number' => '9876543210',
        'status' => VisitorRegistrationStatus::Confirmed,
    ]);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('sendWhatsApp', $member->id);

    expect(ExhibitionVisitor::where('phone_number', '9876543210')->count())->toBe(1);
    Queue::assertPushed(SendWhatsAppCampaign::class, 1);
});

it('sends to all committee members and skips those without a mobile', function () {
    CommitteeMember::factory()->count(3)->create();
    CommitteeMember::factory()->withoutMobile()->create();

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('sendWhatsAppToAll')
        ->assertSet('showSendAllModal', false);

    expect(ExhibitionVisitor::count())->toBe(3);
    Queue::assertPushed(SendWhatsAppCampaign::class, 3);
});

it('does not send when no exhibition is selected', function () {
    CurrentExhibition::clear();
    $member = CommitteeMember::factory()->create(['mobile' => '9876543210']);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->assertDontSee('confirmSendAll', escape: false)
        ->call('sendWhatsApp', $member->id);

    expect(ExhibitionVisitor::count())->toBe(0);
    Queue::assertNothingPushed();
});
