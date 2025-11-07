<?php

declare(strict_types=1);

use App\BookingStatus;
use App\Livewire\Admin\SupportTickets\Index as SupportTicketsIndex;
use App\Livewire\Admin\SupportTickets\Show as SupportTicketsShow;
use App\Models\Booking;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(function () {
    Storage::fake('public');
});

test('customer can view support ticket creation page', function () {
    get(route('support-tickets.create'))
        ->assertSuccessful()
        ->assertSeeLivewire('support.create-ticket');
});

test('customer can verify a rejected booking', function () {
    $booking = Booking::factory()->create([
        'status' => BookingStatus::Rejected,
        'booking_code' => 'TEST1234',
    ]);

    Livewire::test('support.create-ticket')
        ->set('bookingCode', 'TEST1234')
        ->call('verifyBooking')
        ->assertSet('bookingVerified', true)
        ->assertSet('booking.id', $booking->id)
        ->assertHasNoErrors();
});

test('customer cannot verify a pending booking', function () {
    Booking::factory()->create([
        'status' => BookingStatus::PendingApproval,
        'booking_code' => 'TEST1234',
    ]);

    Livewire::test('support.create-ticket')
        ->set('bookingCode', 'TEST1234')
        ->call('verifyBooking')
        ->assertSet('bookingVerified', false)
        ->assertHasErrors('bookingCode');
});

test('customer can upload document via controller', function () {
    $file = UploadedFile::fake()->create('document.pdf', 1024);

    $response = post(route('support-tickets.upload'), [
        'document' => $file,
    ]);

    $response->assertSuccessful()
        ->assertJsonStructure(['path', 'filename']);

    Storage::disk('public')->assertExists($response->json('path'));
});

test('customer cannot upload document larger than 5MB', function () {
    $file = UploadedFile::fake()->create('large-document.pdf', 6000); // 6MB

    post(route('support-tickets.upload'), [
        'document' => $file,
    ])->assertSessionHasErrors('document');
});

test('customer cannot upload unsupported file type', function () {
    $file = UploadedFile::fake()->create('document.exe', 100);

    post(route('support-tickets.upload'), [
        'document' => $file,
    ])->assertSessionHasErrors('document');
});

test('customer can create support ticket with documents', function () {
    $booking = Booking::factory()->create([
        'status' => BookingStatus::Rejected,
        'booking_code' => 'TEST1234',
    ]);

    Livewire::test('support.create-ticket')
        ->set('bookingCode', 'TEST1234')
        ->call('verifyBooking')
        ->set('uploadedDocuments', ['support-tickets/doc1.pdf', 'support-tickets/doc2.pdf'])
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('home'));

    assertDatabaseHas('support_tickets', [
        'booking_id' => $booking->id,
        'status' => 'pending',
    ]);

    expect(SupportTicket::where('booking_id', $booking->id)->exists())->toBeTrue();
});

test('admin can view support tickets list', function () {
    $admin = User::factory()->create(['role' => 'super_admin']);

    $tickets = SupportTicket::factory()->count(3)->create();

    actingAs($admin)
        ->get(route('admin.support-tickets.index'))
        ->assertSuccessful()
        ->assertSeeLivewire(SupportTicketsIndex::class)
        ->assertSee($tickets->first()->ticket_number);
});

test('admin can view single support ticket', function () {
    $admin = User::factory()->create(['role' => 'super_admin']);
    $ticket = SupportTicket::factory()->create();

    actingAs($admin)
        ->get(route('admin.support-tickets.show', $ticket))
        ->assertSuccessful()
        ->assertSeeLivewire(SupportTicketsShow::class)
        ->assertSee($ticket->ticket_number);
});

test('admin can approve support ticket', function () {
    $admin = User::factory()->create(['role' => 'super_admin']);

    $booking = Booking::factory()->create([
        'status' => BookingStatus::Rejected,
    ]);

    $ticket = SupportTicket::factory()->create([
        'booking_id' => $booking->id,
        'status' => 'pending',
    ]);

    actingAs($admin);

    Livewire::test('admin.support-tickets.show', ['ticket' => $ticket])
        ->call('approve')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.support-tickets.index'));

    expect($ticket->fresh()->status)->toBe('approved');
    expect($ticket->fresh()->reviewed_by)->toBe($admin->id);
    expect($ticket->fresh()->booking->status)->toBe(BookingStatus::PaymentPending);
});

test('admin can reject support ticket', function () {
    $admin = User::factory()->create(['role' => 'super_admin']);

    $ticket = SupportTicket::factory()->create([
        'status' => 'pending',
    ]);

    actingAs($admin);

    Livewire::test('admin.support-tickets.show', ['ticket' => $ticket])
        ->set('rejectionReason', 'Documents are not clear')
        ->call('reject')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.support-tickets.index'));

    expect($ticket->fresh()->status)->toBe('rejected');
    expect($ticket->fresh()->rejection_reason)->toBe('Documents are not clear');
    expect($ticket->fresh()->reviewed_by)->toBe($admin->id);
});

test('admin can filter tickets by status', function () {
    $admin = User::factory()->create(['role' => 'super_admin']);

    SupportTicket::factory()->create(['status' => 'pending']);
    SupportTicket::factory()->create(['status' => 'approved']);
    SupportTicket::factory()->create(['status' => 'rejected']);

    actingAs($admin);

    Livewire::test('admin.support-tickets.index')
        ->set('statusFilter', 'pending')
        ->assertSet('statusFilter', 'pending');
});

test('admin can search tickets', function () {
    $admin = User::factory()->create(['role' => 'super_admin']);

    $ticket = SupportTicket::factory()->create([
        'ticket_number' => 'TKT-ABC123',
    ]);

    actingAs($admin);

    Livewire::test('admin.support-tickets.index')
        ->set('search', 'ABC123')
        ->assertSet('search', 'ABC123');
});

test('non-admin cannot access admin support tickets pages', function () {
    $user = User::factory()->create(['role' => 'user']);

    actingAs($user)
        ->get(route('admin.support-tickets.index'))
        ->assertForbidden();
});

test('ticket number is automatically generated', function () {
    $booking = Booking::factory()->create();

    $ticket = SupportTicket::create([
        'booking_id' => $booking->id,
        'documents' => [],
        'status' => 'pending',
    ]);

    expect($ticket->ticket_number)->toStartWith('TKT-');
    expect(strlen($ticket->ticket_number))->toBe(10); // TKT- + 6 chars
});

test('support ticket relationships work correctly', function () {
    $booking = Booking::factory()->create();
    $admin = User::factory()->create();

    $ticket = SupportTicket::factory()->create([
        'booking_id' => $booking->id,
        'reviewed_by' => $admin->id,
    ]);

    expect($ticket->booking)->toBeInstanceOf(Booking::class);
    expect($ticket->reviewedBy)->toBeInstanceOf(User::class);
    expect($booking->supportTickets)->toHaveCount(1);
});
