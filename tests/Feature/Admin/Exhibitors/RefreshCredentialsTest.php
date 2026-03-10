<?php

declare(strict_types=1);

use App\BookingStatus;
use App\Jobs\SendSmsMessage;
use App\Livewire\Admin\Exhibitors\Index;
use App\Models\Booking;
use App\Models\Exhibition;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    Queue::fake();
    config(['services.sms.enabled' => true]);
});

test('admin can open refresh credentials modal', function () {
    $admin = User::factory()->admin()->create();
    $exhibition = Exhibition::factory()->create();
    $exhibitorUser = User::factory()->create(['role' => 'exhibitor']);
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentCompleted,
        'is_manual_block' => false,
        'exhibitor_user_id' => $exhibitorUser->id,
        'login_password' => 'SGCCI@OLDPWD',
    ]);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('openRefreshCredentialsModal', $booking->id, $booking->brand_name)
        ->assertSet('showRefreshCredentialsModal', true)
        ->assertSet('confirmRefreshId', $booking->id)
        ->assertSet('confirmRefreshName', $booking->brand_name);
});

test('admin can refresh credentials and new password is generated', function () {
    $admin = User::factory()->admin()->create();
    $exhibition = Exhibition::factory()->create();
    $exhibitorUser = User::factory()->create(['role' => 'exhibitor', 'password' => Hash::make('SGCCI@OLDPWD')]);
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentCompleted,
        'is_manual_block' => false,
        'exhibitor_user_id' => $exhibitorUser->id,
        'login_password' => 'SGCCI@OLDPWD',
    ]);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('openRefreshCredentialsModal', $booking->id, $booking->brand_name)
        ->call('refreshCredentials')
        ->assertSet('showRefreshCredentialsModal', false);

    $booking->refresh();
    expect($booking->login_password)->not->toBe('SGCCI@OLDPWD');
    expect($booking->login_password)->toStartWith('SGCCI@');

    $exhibitorUser->refresh();
    expect(Hash::check('SGCCI@OLDPWD', $exhibitorUser->password))->toBeFalse();
});

test('refresh credentials dispatches sms', function () {
    $admin = User::factory()->admin()->create();
    $exhibition = Exhibition::factory()->create();
    $exhibitorUser = User::factory()->create(['role' => 'exhibitor']);
    $booking = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentCompleted,
        'is_manual_block' => false,
        'exhibitor_user_id' => $exhibitorUser->id,
        'login_password' => 'SGCCI@OLDPWD',
    ]);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('openRefreshCredentialsModal', $booking->id, $booking->brand_name)
        ->call('refreshCredentials');

    Queue::assertPushed(SendSmsMessage::class);
});

test('non-admin cannot access exhibitors index', function () {
    $user = User::factory()->create(['role' => 'exhibitor']);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertForbidden();
});

test('admin can bulk refresh credentials for exhibitors with existing passwords', function () {
    $admin = User::factory()->admin()->create();
    $exhibition = Exhibition::factory()->create();

    $exhibitorUser1 = User::factory()->create(['role' => 'exhibitor']);
    $booking1 = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentCompleted,
        'is_manual_block' => false,
        'exhibitor_user_id' => $exhibitorUser1->id,
        'login_password' => 'SGCCI@FIRST1',
    ]);

    $exhibitorUser2 = User::factory()->create(['role' => 'exhibitor']);
    $booking2 = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentCompleted,
        'is_manual_block' => false,
        'exhibitor_user_id' => $exhibitorUser2->id,
        'login_password' => 'SGCCI@SECOND',
    ]);

    // This one has no credentials — should be skipped
    $booking3 = Booking::factory()->create([
        'exhibition_id' => $exhibition->id,
        'status' => BookingStatus::PaymentCompleted,
        'is_manual_block' => false,
        'login_password' => null,
    ]);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->set('selectedBookings', [$booking1->id, $booking2->id, $booking3->id])
        ->call('bulkRefreshCredentials')
        ->assertSet('showBulkRefreshConfirmModal', false)
        ->assertSet('selectedBookings', []);

    $booking1->refresh();
    $booking2->refresh();
    $booking3->refresh();

    expect($booking1->login_password)->not->toBe('SGCCI@FIRST1');
    expect($booking2->login_password)->not->toBe('SGCCI@SECOND');
    expect($booking3->login_password)->toBeNull();

    Queue::assertPushed(SendSmsMessage::class, 2);
});
