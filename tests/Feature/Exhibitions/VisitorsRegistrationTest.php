<?php

declare(strict_types=1);

use App\Livewire\Exhibitions\VisitorsRegistration;
use App\Models\Exhibition;
use App\Models\ExhibitionVisitor;
use App\VisitorRegistrationStatus;
use Livewire\Livewire;

/**
 * Fill in all required step 1 fields on the Livewire component test.
 */
function setStep1Fields(mixed $component, string $phone = '9825145600'): mixed
{
    return $component
        ->set('phoneNumber', $phone)
        ->set('name', 'Test Visitor')
        ->set('companyName', 'Test Co')
        ->set('state', 'Gujarat')
        ->set('city', 'Surat')
        ->set('segment', 'Business');
}

test('free exhibition creates a confirmed visitor on first registration', function () {
    $exhibition = Exhibition::factory()->create();

    $component = Livewire::test(VisitorsRegistration::class, ['exhibition' => $exhibition]);
    setStep1Fields($component)->call('register')->assertHasNoErrors();

    $visitor = ExhibitionVisitor::where('exhibition_id', $exhibition->id)
        ->where('phone_number', '9825145600')
        ->first();

    expect($visitor)->not->toBeNull()
        ->and($visitor->status)->toBe(VisitorRegistrationStatus::Confirmed);
});

test('paid exhibition creates a payment_pending visitor on first registration', function () {
    $exhibition = Exhibition::factory()->paid(150.00)->create();

    $component = Livewire::test(VisitorsRegistration::class, ['exhibition' => $exhibition]);
    setStep1Fields($component)->call('register')->assertHasNoErrors();

    $visitor = ExhibitionVisitor::where('exhibition_id', $exhibition->id)
        ->where('phone_number', '9825145600')
        ->first();

    expect($visitor)->not->toBeNull()
        ->and($visitor->status)->toBe(VisitorRegistrationStatus::PaymentPending)
        ->and((float) $visitor->payment_amount)->toBe(150.00);
});

test('retrying registration after abandoned payment reuses the existing record', function () {
    $exhibition = Exhibition::factory()->paid(150.00)->create();

    // Simulate a prior incomplete registration (payment abandoned / never completed)
    $existing = ExhibitionVisitor::factory()
        ->for($exhibition)
        ->paymentPending()
        ->create([
            'phone_number' => '9825145600',
            'name' => 'Old Name',
            'payment_amount' => 150.00,
        ]);

    $component = Livewire::test(VisitorsRegistration::class, ['exhibition' => $exhibition]);
    setStep1Fields($component, '9825145600')
        ->set('name', 'Updated Name') // visitor corrected details on retry
        ->call('register')
        ->assertHasNoErrors();

    // Still only one record for this phone + exhibition
    expect(
        ExhibitionVisitor::where('exhibition_id', $exhibition->id)
            ->where('phone_number', '9825145600')
            ->count()
    )->toBe(1);

    $existing->refresh();

    expect($existing->name)->toBe('Updated Name')  // details updated
        ->and($existing->status)->toBe(VisitorRegistrationStatus::PaymentPending)
        ->and($existing->payment_transaction_id)->toBeNull() // payment data cleared
        ->and($existing->payment_status)->toBeNull();
});

test('retrying registration after payment_failed status reuses the existing record', function () {
    $exhibition = Exhibition::factory()->paid(200.00)->create();

    ExhibitionVisitor::factory()
        ->for($exhibition)
        ->state(['status' => 'payment_failed', 'phone_number' => '9825145600', 'payment_amount' => 200.00])
        ->create();

    $component = Livewire::test(VisitorsRegistration::class, ['exhibition' => $exhibition]);
    setStep1Fields($component, '9825145600')->call('register')->assertHasNoErrors();

    expect(
        ExhibitionVisitor::where('exhibition_id', $exhibition->id)
            ->where('phone_number', '9825145600')
            ->count()
    )->toBe(1);

    $visitor = ExhibitionVisitor::where('exhibition_id', $exhibition->id)
        ->where('phone_number', '9825145600')
        ->first();

    expect($visitor->status)->toBe(VisitorRegistrationStatus::PaymentPending);
});

test('confirmed visitor cannot register again for the same exhibition', function () {
    $exhibition = Exhibition::factory()->create();

    ExhibitionVisitor::factory()
        ->for($exhibition)
        ->create(['phone_number' => '9825145600']);

    $component = Livewire::test(VisitorsRegistration::class, ['exhibition' => $exhibition]);
    setStep1Fields($component, '9825145600')->call('nextStep')->assertHasErrors(['phoneNumber']);
});
