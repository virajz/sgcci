<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\ExhibitorBadgeController;
use App\Http\Controllers\ExhibitorScanController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\VisitorPassController;
use App\Http\Controllers\VisitorPaymentController;
use App\Http\Controllers\VisitorScanController;
use App\Livewire\Admin\DatabaseBackups;
use App\Livewire\Admin\Exhibitions\Index as ExhibitionsIndex;
use App\Livewire\Admin\Exhibitors\Badges as AdminExhibitorBadges;
use App\Livewire\Admin\Exhibitors\Index as ExhibitorsIndex;
use App\Livewire\Admin\Exhibitors\InvitedGuests as AdminExhibitorInvitedGuests;
use App\Livewire\Admin\Inquiries\EditBooking as AdminEditBooking;
use App\Livewire\Admin\Inquiries\Index as InquiriesIndex;
use App\Livewire\Admin\Inquiries\Show as InquiriesShow;
use App\Livewire\Admin\Scans\Index as ScansIndex;
use App\Livewire\Admin\StaffMembers\Index as StaffMembersIndex;
use App\Livewire\Admin\SupportTickets\Index as SupportTicketsIndex;
use App\Livewire\Admin\SupportTickets\Show as SupportTicketsShow;
use App\Livewire\Admin\Visitors\Index as VisitorsIndex;
use App\Livewire\Admin\Visitors\Show as VisitorsShow;
use App\Livewire\Admin\WalkInVisitors\Index as WalkInVisitorsIndex;
use App\Livewire\Dashboard;
use App\Livewire\Exhibitions\Booking;
use App\Livewire\Exhibitions\Confirmation;
use App\Livewire\Exhibitions\EditBooking;
use App\Livewire\Exhibitions\ProductProfileSelection;
use App\Livewire\Exhibitions\ThankYou;
use App\Livewire\Exhibitions\VisitorsRegistration;
use App\Livewire\Exhibitions\VisitorThankYou;
use App\Livewire\Exhibitor\Badges as ExhibitorBadges;
use App\Livewire\Exhibitor\CompanyProfile as ExhibitorCompanyProfile;
use App\Livewire\Exhibitor\InvitedGuests as ExhibitorInvitedGuests;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\TwoFactor;
use App\Livewire\Support\CreateTicket;
use App\Livewire\Support\TicketThankYou;
use App\Models\Exhibition;
use App\Models\User;
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
Route::post('booking/upload-logo', [BookingController::class, 'uploadLogo'])->name('booking.upload-logo');
Route::get('booking/download-logo/{path}', [BookingController::class, 'downloadLogo'])->name('booking.download-logo')->where('path', '.*');

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

// Visitor payment routes
Route::prefix('visitor-payment')->name('visitor-payment.')->group(function () {
    Route::get('/{registrationCode}', [VisitorPaymentController::class, 'initiate'])->name('initiate');
    Route::post('/response', [VisitorPaymentController::class, 'response'])->name('response');
    Route::post('/cancel', [VisitorPaymentController::class, 'cancel'])->name('cancel');
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

    // Exhibitor routes
    Route::prefix('exhibitor')->name('exhibitor.')->group(function () {
        Route::get('badges', ExhibitorBadges::class)->name('badges.index');
        Route::get('company-profile', ExhibitorCompanyProfile::class)->name('company-profile');
        Route::get('invited-guests', ExhibitorInvitedGuests::class)->name('invited-guests');
        Route::get('badges/{booking}/{member}/image', [ExhibitorBadgeController::class, 'inline'])->name('badges.inline');
        Route::get('badges/{booking}/{member}/photo', [ExhibitorBadgeController::class, 'photo'])->name('badges.photo');
        Route::get('badges/{booking}/{member}/download', [ExhibitorBadgeController::class, 'download'])->name('badges.download');
        Route::get('badges/{booking}/download-all', [ExhibitorBadgeController::class, 'downloadAll'])->name('badges.download-all');
        Route::get('{booking}/profile-media', [ExhibitorBadgeController::class, 'profileMedia'])->name('profile-media');
    });

    // Admin routes
    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        Route::get('exhibitions', ExhibitionsIndex::class)->name('exhibitions.index');
        Route::get('exhibitors', ExhibitorsIndex::class)->name('exhibitors.index');
        Route::get('exhibitors/{booking}/badges', AdminExhibitorBadges::class)->name('exhibitors.badges');
        Route::get('exhibitors/{booking}/invited-guests', AdminExhibitorInvitedGuests::class)->name('exhibitors.invited-guests');
        Route::get('exhibitors/download-svgs', [ExhibitorBadgeController::class, 'downloadAllSvgs'])->name('exhibitors.download-svgs');
        Route::get('exhibitors/{booking}/download-svg', [ExhibitorBadgeController::class, 'downloadSvg'])->name('exhibitors.download-svg');
        Route::get('exhibitors/{booking}/print-badges', [ExhibitorBadgeController::class, 'printAll'])->name('exhibitors.print-badges');
        Route::get('inquiries', InquiriesIndex::class)->name('inquiries.index');
        Route::get('inquiries/{booking}', InquiriesShow::class)->name('inquiries.show');
        Route::get('inquiries/{booking}/edit', AdminEditBooking::class)->name('inquiries.edit');
        Route::get('visitors', VisitorsIndex::class)->name('visitors.index');
        Route::get('visitors/{visitor}', VisitorsShow::class)->name('visitors.show');
        Route::get('walk-in-visitors', WalkInVisitorsIndex::class)->name('walk-in-visitors.index');
        Route::get('scans', ScansIndex::class)->name('scans.index');
        Route::get('staff-members', StaffMembersIndex::class)->name('staff-members.index');
        Route::get('support-tickets', SupportTicketsIndex::class)->name('support-tickets.index');
        Route::get('support-tickets/{ticket}', SupportTicketsShow::class)->name('support-tickets.show');
        Route::get('database-backups', DatabaseBackups::class)->name('database-backups');
    });

    // Front desk routes
    Route::prefix('front-desk')->name('front-desk.')->middleware('front_desk')->group(function () {
        Route::get('/', \App\Livewire\FrontDesk\Index::class)->name('index');
    });

    // Security desk routes
    Route::prefix('security-desk')->name('security-desk.')->middleware('security_desk')->group(function () {
        Route::get('/', \App\Livewire\SecurityDesk\Index::class)->name('index');
    });
});

// Walk-in visitor badge routes (auth required, controller handles role check)
Route::middleware(['auth'])->group(function () {
    Route::get('front-desk/visitor/{registrationCode}/badge', [VisitorPassController::class, 'walkInBadgeInline'])->name('front-desk.visitor.badge.inline');
    Route::get('front-desk/visitor/{registrationCode}/badge/download', [VisitorPassController::class, 'walkInBadgeDownload'])->name('front-desk.visitor.badge.download');
    Route::get('front-desk/visitor/{registrationCode}/print', [VisitorPassController::class, 'printBadge'])->name('front-desk.visitor.badge.print');
});

// Public visitor registration (no auth required)
Route::get('{exhibition:slug}/visitors-registration', VisitorsRegistration::class)->name('visitors-registration');
Route::get('{exhibition:slug}/visitors-registration/thank-you/{registrationCode}', VisitorThankYou::class)->name('visitors-registration.thank-you');
Route::get('{exhibition:slug}/visitor-pass/{registrationCode}/download', [VisitorPassController::class, 'download'])->name('visitor-pass.download');
Route::get('{exhibition:slug}/visitor-pass/{registrationCode}/image', [VisitorPassController::class, 'inline'])->name('visitor-pass.image');

// Smart QR scan URL — redirects based on auth/role
Route::get('{exhibition:slug}/visitors/{registrationCode}', VisitorScanController::class)->name('visitor.scan');

// Exhibitor QR scan URL — redirects: admin→admin badges, exhibitor→own badges, public→WhatsApp
Route::get('exhibitor/{bookingCode}', ExhibitorScanController::class)->name('exhibitor.scan');

// Route::get('temp', function () {
//     User::factory()->create([
//         'name' => 'Gopal',
//         'email' => 'gopal@sgcci.in',
//         'role' => 'admin',
//         'password' => bcrypt('wF3%tY6^hJ9*bM1&sP4#oL2'),
//         'two_factor_secret' => null,
//         'two_factor_recovery_codes' => null,
//         'two_factor_confirmed_at' => null,
//     ]);
// });

require __DIR__.'/auth.php';
