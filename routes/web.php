<?php

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SupportTicketController;
use App\Livewire\Admin\Inquiries\EditBooking as AdminEditBooking;
use App\Livewire\Admin\Inquiries\Index as InquiriesIndex;
use App\Livewire\Admin\Inquiries\Show as InquiriesShow;
use App\Livewire\Admin\StaffMembers\Index as StaffMembersIndex;
use App\Livewire\Admin\SupportTickets\Index as SupportTicketsIndex;
use App\Livewire\Admin\SupportTickets\Show as SupportTicketsShow;
use App\Livewire\Dashboard;
use App\Livewire\Exhibitions\Booking;
use App\Livewire\Exhibitions\Confirmation;
use App\Livewire\Exhibitions\EditBooking;
use App\Livewire\Exhibitions\ProductProfileSelection;
use App\Livewire\Exhibitions\ThankYou;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\TwoFactor;
use App\Livewire\Support\CreateTicket;
use App\Livewire\Support\TicketThankYou;
use App\Models\Exhibition;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::get('/', function () {
    $exhibition = Exhibition::first();

    return view('welcome', compact('exhibition'));
})->name('welcome');

Route::redirect('/home', '/')->name('home');

Route::get('exhibitions/{exhibition}/product-profile', ProductProfileSelection::class)->name('exhibitions.product-profile.select');
Route::get('exhibitions/{exhibition}/booking', Booking::class)->name('exhibitions.booking.show');
Route::get('exhibitions/{exhibition}/booking/confirmation', Confirmation::class)->name('exhibitions.booking.confirmation');
Route::get('exhibitions/{exhibition}/booking/thank-you/{bookingCode}', ThankYou::class)->name('exhibitions.booking.thank-you');
Route::get('edit-booking', EditBooking::class)->name('exhibitions.booking.edit');

// Support Ticket routes
Route::get('support-tickets/create', CreateTicket::class)->name('support-tickets.create');
Route::get('support-tickets/thank-you/{ticketNumber}', TicketThankYou::class)->name('support-tickets.thank-you');
Route::post('support-tickets/upload', [SupportTicketController::class, 'upload'])->name('support-tickets.upload');
Route::get('support-tickets/download/{path}', [SupportTicketController::class, 'download'])->name('support-tickets.download')->where('path', '.*');

// Payment routes
Route::prefix('payment')->name('payment.')->group(function () {
    Route::get('/{bookingCode}', [PaymentController::class, 'initiate'])->name('initiate');
    Route::post('/response', [PaymentController::class, 'response'])->name('response');
    Route::post('/cancel', [PaymentController::class, 'cancel'])->name('cancel');
});

Route::get('dashboard', Dashboard::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('settings.profile');
    Route::get('settings/password', Password::class)->name('settings.password');
    Route::get('settings/appearance', Appearance::class)->name('settings.appearance');

    Route::get('settings/two-factor', TwoFactor::class)
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');

    // Admin routes
    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        Route::get('inquiries', InquiriesIndex::class)->name('inquiries.index');
        Route::get('inquiries/{booking}', InquiriesShow::class)->name('inquiries.show');
        Route::get('inquiries/{booking}/edit', AdminEditBooking::class)->name('inquiries.edit');
        Route::get('staff-members', StaffMembersIndex::class)->name('staff-members.index');
        Route::get('support-tickets', SupportTicketsIndex::class)->name('support-tickets.index');
        Route::get('support-tickets/{ticket}', SupportTicketsShow::class)->name('support-tickets.show');
    });
});

require __DIR__.'/auth.php';
