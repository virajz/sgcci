<?php

declare(strict_types=1);

use App\Livewire\Exhibitor\InvitationPass;
use App\Models\Booking;
use App\Models\Exhibition;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');

    $background = UploadedFile::fake()->image('invitation.jpg', 1672, 941);
    $bgPath = $background->storeAs('exhibitions/seed', 'invitation.jpg', 'public');

    $this->exhibition = Exhibition::factory()->create([
        'invitation_background_path' => $bgPath,
        'invitation_stall_x' => 836,
        'invitation_stall_y' => 600,
        'invitation_company_x' => 836,
        'invitation_company_y' => 700,
        'invitation_logo_x' => 91,
        'invitation_logo_y' => 73,
        'invitation_logo_size' => 376,
        'invitation_text_color' => '#39318a',
    ]);

    $this->exhibitorUser = User::factory()->create(['role' => 'exhibitor']);

    $this->booking = Booking::factory()->create([
        'exhibition_id' => $this->exhibition->id,
        'exhibitor_user_id' => $this->exhibitorUser->id,
        'brand_name' => 'Acme Jewels',
        'selected_stalls' => ['A-42'],
    ]);
});

it('forbids non-exhibitors from accessing the invitation pass page', function () {
    $user = User::factory()->create(['role' => 'user']);

    $this->actingAs($user)
        ->get(route('exhibitor.invitation-pass'))
        ->assertForbidden();
});

it('renders the invitation pass page for an exhibitor', function () {
    $this->actingAs($this->exhibitorUser)
        ->get(route('exhibitor.invitation-pass'))
        ->assertSuccessful()
        ->assertSeeLivewire(InvitationPass::class);
});

it('pre-fills stall number and name from the booking', function () {
    Livewire::actingAs($this->exhibitorUser)
        ->test(InvitationPass::class)
        ->assertSet('stallNo', 'A-42')
        ->assertSet('companyName', 'Acme Jewels');
});

it('saves the logo, stall number and name onto the booking', function () {
    $logo = UploadedFile::fake()->image('logo.png', 400, 400);

    Livewire::actingAs($this->exhibitorUser)
        ->test(InvitationPass::class)
        ->set('logoUpload', $logo)
        ->set('stallNo', 'B-17')
        ->set('companyName', 'Acme Diamonds')
        ->call('save')
        ->assertHasNoErrors();

    $this->booking->refresh();

    expect($this->booking->invitation_logo_path)->not->toBeNull();
    expect($this->booking->invitation_stall_no)->toBe('B-17');
    expect($this->booking->invitation_company_name)->toBe('Acme Diamonds');

    Storage::disk('public')->assertExists($this->booking->invitation_logo_path);
});

it('validates that stall number and name are required', function () {
    Livewire::actingAs($this->exhibitorUser)
        ->test(InvitationPass::class)
        ->set('stallNo', '')
        ->set('companyName', '')
        ->call('save')
        ->assertHasErrors(['stallNo', 'companyName']);
});

it('removes the saved logo', function () {
    $logo = UploadedFile::fake()->image('logo.png');
    $path = $logo->storeAs('exhibitions/seed', 'logo.png', 'public');
    $this->booking->update(['invitation_logo_path' => $path]);

    Livewire::actingAs($this->exhibitorUser)
        ->test(InvitationPass::class)
        ->call('removeLogo');

    $this->booking->refresh();

    expect($this->booking->invitation_logo_path)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

it('downloads a composited invitation pass as a jpeg', function () {
    $this->booking->update([
        'invitation_stall_no' => 'A-42',
        'invitation_company_name' => 'Acme Jewels',
    ]);

    $response = $this->actingAs($this->exhibitorUser)
        ->get(route('exhibitor.invitation-pass.download', $this->booking));

    $response->assertSuccessful();
    expect($response->headers->get('Content-Type'))->toBe('image/jpeg');
});

it('forbids downloading another exhibitor\'s invitation pass', function () {
    $otherUser = User::factory()->create(['role' => 'exhibitor']);

    $this->actingAs($otherUser)
        ->get(route('exhibitor.invitation-pass.download', $this->booking))
        ->assertForbidden();
});
