<?php

declare(strict_types=1);

use App\Jobs\SendWhatsAppCampaign;
use App\Livewire\Admin\Members\Index;
use App\Models\Exhibition;
use App\Models\ExhibitionVisitor;
use App\Models\Member;
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

it('registers a member and sends the pass over WhatsApp', function () {
    $member = Member::factory()->create([
        'contact_name' => 'Ravi Shah',
        'company' => 'Shah Textiles',
        'cell_no' => '9876543210',
    ]);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('sendWhatsApp', $member->id);

    $visitor = ExhibitionVisitor::where('phone_number', '9876543210')->first();

    expect($visitor)->not->toBeNull()
        ->and($visitor->exhibition_id)->toBe($this->exhibition->id)
        ->and($visitor->name)->toBe('Ravi Shah')
        ->and($visitor->company_name)->toBe('Shah Textiles')
        ->and($visitor->status)->toBe(VisitorRegistrationStatus::Confirmed)
        ->and($visitor->source)->toBe('member');

    Queue::assertPushed(SendWhatsAppCampaign::class, function ($job) {
        return $job->campaignName === 'Paidregistration1'
            && $job->phoneNumber === '9876543210'
            && $job->templateParams[4] === 'Shah Textiles';
    });
});

it('opens the confirmation modal naming the member', function () {
    $member = Member::factory()->create(['contact_name' => 'Ravi Shah', 'cell_no' => '9876543210']);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('confirmSend', $member->id)
        ->assertSet('showSendModal', true)
        ->assertSet('memberToSend', $member->id)
        ->assertSet('memberToSendName', 'Ravi Shah');

    expect(ExhibitionVisitor::count())->toBe(0);
    Queue::assertNothingPushed();
});

it('does not open the confirmation modal when no exhibition is selected', function () {
    CurrentExhibition::clear();
    $member = Member::factory()->create(['cell_no' => '9876543210']);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('confirmSend', $member->id)
        ->assertSet('showSendModal', false);
});

it('skips a member without a cell number', function () {
    $member = Member::factory()->withoutCell()->create();

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('sendWhatsApp', $member->id);

    expect(ExhibitionVisitor::count())->toBe(0);
    Queue::assertNothingPushed();
});

it('sends to all members and skips those without a cell number', function () {
    Member::factory()->count(3)->create();
    Member::factory()->withoutCell()->create();

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('sendWhatsAppToAll')
        ->assertSet('showSendAllModal', false);

    expect(ExhibitionVisitor::count())->toBe(3);
    Queue::assertPushed(SendWhatsAppCampaign::class, 3);
});

it('does not send when no exhibition is selected', function () {
    CurrentExhibition::clear();
    $member = Member::factory()->create(['cell_no' => '9876543210']);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->assertDontSee('confirmSendAll', escape: false)
        ->call('sendWhatsApp', $member->id);

    expect(ExhibitionVisitor::count())->toBe(0);
    Queue::assertNothingPushed();
});
