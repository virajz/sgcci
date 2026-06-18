<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\CommitteeMemberPhotoController;
use App\Http\Controllers\ExhibitorBadgeController;
use App\Http\Controllers\ExhibitorScanController;
use App\Http\Controllers\MemberBadgeController;
use App\Http\Controllers\MemberScanController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\VisitorPassController;
use App\Http\Controllers\VisitorPaymentController;
use App\Http\Controllers\VisitorScanController;
use App\Http\Controllers\WhatsAppWebhookController;
use App\Livewire\Admin\Analytics\ExhibitorLeads as AnalyticsExhibitorLeads;
use App\Livewire\Admin\CommitteeMembers\Import as CommitteeMembersImport;
use App\Livewire\Admin\CommitteeMembers\ImportPhotos as CommitteeMembersImportPhotos;
use App\Livewire\Admin\CommitteeMembers\Index as CommitteeMembersIndex;
use App\Livewire\Admin\DatabaseBackups;
use App\Livewire\Admin\Exhibitions\Index as ExhibitionsIndex;
use App\Livewire\Admin\Exhibitors\Badges as AdminExhibitorBadges;
use App\Livewire\Admin\Exhibitors\Index as ExhibitorsIndex;
use App\Livewire\Admin\Exhibitors\InvitedGuests as AdminExhibitorInvitedGuests;
use App\Livewire\Admin\Inquiries\EditBooking as AdminEditBooking;
use App\Livewire\Admin\Inquiries\Index as InquiriesIndex;
use App\Livewire\Admin\Inquiries\Show as InquiriesShow;
use App\Livewire\Admin\Members\Create as MembersCreate;
use App\Livewire\Admin\Members\Import as MembersImport;
use App\Livewire\Admin\Members\Index as MembersIndex;
use App\Livewire\Admin\Members\Show as MembersShow;
use App\Livewire\Admin\Scans\Index as ScansIndex;
use App\Livewire\Admin\Segments\Index as SegmentsIndex;
use App\Livewire\Admin\Segments\SubSegments as SegmentsSubSegments;
use App\Livewire\Admin\StaffMembers\Index as StaffMembersIndex;
use App\Livewire\Admin\SupportTickets\Index as SupportTicketsIndex;
use App\Livewire\Admin\SupportTickets\Show as SupportTicketsShow;
use App\Livewire\Admin\Visitors\Add;
use App\Livewire\Admin\Visitors\Index as VisitorsIndex;
use App\Livewire\Admin\Visitors\Show as VisitorsShow;
use App\Livewire\Admin\WalkInVisitors\Index as WalkInVisitorsIndex;
use App\Livewire\Admin\WhatsAppWebhookLogs\Index as WhatsAppWebhookLogsIndex;
use App\Livewire\Dashboard;
use App\Livewire\Exhibitions\VisitorsRegistration;
use App\Livewire\Exhibitions\VisitorThankYou;
use App\Livewire\Exhibitor\Badges as ExhibitorBadges;
use App\Livewire\Exhibitor\CompanyProfile as ExhibitorCompanyProfile;
use App\Livewire\Exhibitor\InvitationPass as ExhibitorInvitationPass;
use App\Livewire\Exhibitor\InvitedGuests as ExhibitorInvitedGuests;
use App\Livewire\Exhibitor\Leads as ExhibitorLeads;
use App\Livewire\FrontDesk\Index;
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
    $today = now()->toDateString();

    $exhibitions = Exhibition::query()
        ->where('registration_closed', false)
        ->whereDate('end_date', '>=', $today)
        ->whereDate('end_date', '<=', now()->addMonth()->toDateString())
        ->orderBy('start_date')
        ->get();

    return view('welcome', compact('exhibitions'));
})->name('welcome');

Route::redirect('/home', '/')->name('home');

Route::get('exhibitions/{exhibition}/product-profile', fn () => redirect()->route('welcome'))->name('exhibitions.product-profile.select');
Route::get('exhibitions/{exhibition}/booking', fn () => redirect()->route('welcome'))->name('exhibitions.booking.show');
Route::get('exhibitions/{exhibition}/booking/confirmation', fn () => redirect()->route('welcome'))->name('exhibitions.booking.confirmation');
Route::get('exhibitions/{exhibition}/booking/thank-you/{bookingCode}', fn () => redirect()->route('welcome'))->name('exhibitions.booking.thank-you');
Route::get('edit-booking', fn () => redirect()->route('welcome'))->name('exhibitions.booking.edit');
Route::post('booking/upload-logo', [BookingController::class, 'uploadLogo'])->name('booking.upload-logo');
Route::get('booking/download-logo/{path}', [BookingController::class, 'downloadLogo'])->name('booking.download-logo')->where('path', '.*');

// Support Ticket routes
Route::get('support-tickets/create', CreateTicket::class)->name('support-tickets.create');
Route::get('support-tickets/thank-you/{ticketNumber}', TicketThankYou::class)->name('support-tickets.thank-you');
Route::post('support-tickets/upload', [SupportTicketController::class, 'upload'])->name('support-tickets.upload');
Route::get('support-tickets/download/{path}', [SupportTicketController::class, 'download'])->name('support-tickets.download')->where('path', '.*');

// WhatsApp webhook — public, CSRF excluded in bootstrap/app.php
Route::post('webhook/whatsapp', [WhatsAppWebhookController::class, 'receive'])->name('webhook.whatsapp');

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
        Route::get('invitation-pass', ExhibitorInvitationPass::class)->name('invitation-pass');
        Route::get('invitation-pass/{booking}/preview', [ExhibitorBadgeController::class, 'invitationPassInline'])->name('invitation-pass.preview');
        Route::get('invitation-pass/{booking}/download', [ExhibitorBadgeController::class, 'invitationPassDownload'])->name('invitation-pass.download');
        Route::get('invited-guests', ExhibitorInvitedGuests::class)->name('invited-guests');
        Route::get('leads', ExhibitorLeads::class)->name('leads');
        Route::get('badges/{booking}/{member}/image', [ExhibitorBadgeController::class, 'inline'])->name('badges.inline');
        Route::get('badges/{booking}/{member}/photo', [ExhibitorBadgeController::class, 'photo'])->name('badges.photo');
        Route::get('badges/{booking}/{member}/download', [ExhibitorBadgeController::class, 'download'])->name('badges.download');
        Route::get('badges/{booking}/download-all', [ExhibitorBadgeController::class, 'downloadAll'])->name('badges.download-all');
        Route::get('{booking}/profile-media', [ExhibitorBadgeController::class, 'profileMedia'])->name('profile-media');
        Route::get('{booking}/profile-media-2', [ExhibitorBadgeController::class, 'profileMedia2'])->name('profile-media-2');
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
        Route::get('visitors/add', Add::class)->name('visitors.add');
        Route::get('visitors/{visitor}', VisitorsShow::class)->name('visitors.show');
        Route::get('walk-in-visitors', WalkInVisitorsIndex::class)->name('walk-in-visitors.index');
        Route::get('walk-in-visitors/print-badges', [VisitorPassController::class, 'printMultiple'])->name('walk-in-visitors.print-badges');
        Route::get('scans', ScansIndex::class)->name('scans.index');
        Route::get('staff-members', StaffMembersIndex::class)->name('staff-members.index');
        Route::get('segments', SegmentsIndex::class)->name('segments.index');
        Route::get('segments/{segment}/sub-segments', SegmentsSubSegments::class)->name('segments.sub-segments');
        Route::get('support-tickets', SupportTicketsIndex::class)->name('support-tickets.index');
        Route::get('support-tickets/{ticket}', SupportTicketsShow::class)->name('support-tickets.show');
        Route::get('analytics/exhibitor-leads', AnalyticsExhibitorLeads::class)->name('analytics.exhibitor-leads');
        Route::get('database-backups', DatabaseBackups::class)->name('database-backups');
        Route::get('whatsapp-webhook-logs', WhatsAppWebhookLogsIndex::class)
            ->name('whatsapp-webhook-logs')
            ->can('viewWhatsAppWebhookLogs');

        // Members section
        Route::prefix('committee-members')->name('committee-members.')->group(function () {
            Route::get('/', CommitteeMembersIndex::class)->name('index');
            Route::get('import', CommitteeMembersImport::class)->name('import');
            Route::get('import-photos', CommitteeMembersImportPhotos::class)->name('import-photos');
            Route::get('{committeeMember}/photo', [CommitteeMemberPhotoController::class, 'show'])->name('photo');
        });

        Route::prefix('members')->name('members.')->group(function () {
            Route::get('/', MembersIndex::class)->name('index');
            Route::get('create', MembersCreate::class)->name('create');
            Route::get('import', MembersImport::class)->name('import');
            Route::get('{member}', MembersShow::class)->name('show');
        });
    });

    // Member badge routes — accessible to admin and front desk
    Route::prefix('admin/committee-members')->name('admin.committee-members.')->group(function () {
        Route::get('{committeeMember}/badge', [MemberBadgeController::class, 'committeeInline'])->name('badge.inline');
        Route::get('{committeeMember}/badge/print', [MemberBadgeController::class, 'committeePrint'])->name('badge.print');
    });

    Route::prefix('admin/members')->name('admin.members.')->group(function () {
        Route::get('{member}/badge', [MemberBadgeController::class, 'memberInline'])->name('badge.inline');
        Route::get('{member}/badge/print', [MemberBadgeController::class, 'memberPrint'])->name('badge.print');
    });

    // Front desk routes
    Route::prefix('front-desk')->name('front-desk.')->middleware('front_desk')->group(function () {
        Route::get('/', Index::class)->name('index');
    });

    // Security desk routes
    Route::prefix('security-desk')->name('security-desk.')->middleware('security_desk')->group(function () {
        Route::get('/', App\Livewire\SecurityDesk\Index::class)->name('index');
    });

});

// Camera QR scanner — public, no login required
Route::get('camera', App\Livewire\Camera\Index::class)->name('camera');

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

// Member QR scan URL — redirects based on role to admin/front-desk/security-desk
Route::middleware(['auth'])->get('members/{membershipNumber}/scan', MemberScanController::class)->name('member.scan');

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
