<?php

use App\Models\StaffMember;
use App\Models\User;
use Livewire\Livewire;

it('allows admin to view staff members page', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get('/admin/staff-members')
        ->assertSuccessful();
});

it('displays all staff members', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $staff1 = StaffMember::factory()->create(['name' => 'John Doe']);
    $staff2 = StaffMember::factory()->create(['name' => 'Jane Smith']);

    $this->actingAs($admin);

    Livewire::test(\App\Livewire\Admin\StaffMembers\Index::class)
        ->assertSee('John Doe')
        ->assertSee('Jane Smith');
});

it('allows admin to add a new staff member', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin);

    Livewire::test(\App\Livewire\Admin\StaffMembers\Index::class)
        ->call('openAddModal')
        ->set('name', 'New Staff Member')
        ->set('phoneCode', '+91')
        ->set('phoneNumber', '9876543210')
        ->set('isActive', true)
        ->call('addStaffMember')
        ->assertHasNoErrors();

    expect(StaffMember::where('phone_number', '9876543210')->exists())->toBeTrue();
});

it('normalizes phone number when adding staff member', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin);

    Livewire::test(\App\Livewire\Admin\StaffMembers\Index::class)
        ->call('openAddModal')
        ->set('name', 'Test Staff')
        ->set('phoneCode', '+91')
        ->set('phoneNumber', '+9876543210') // With leading +
        ->set('isActive', true)
        ->call('addStaffMember')
        ->assertHasNoErrors();

    $staff = StaffMember::where('name', 'Test Staff')->first();
    expect($staff->phone_number)->toBe('9876543210'); // Should be normalized
});

it('defaults to India country code if not provided', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin);

    Livewire::test(\App\Livewire\Admin\StaffMembers\Index::class)
        ->call('openAddModal')
        ->set('name', 'Test Staff')
        ->set('phoneCode', '')
        ->set('phoneNumber', '9876543210')
        ->set('isActive', true)
        ->call('addStaffMember')
        ->assertHasNoErrors();

    $staff = StaffMember::where('name', 'Test Staff')->first();
    expect($staff->phone_code)->toBe('+91');
});

it('allows admin to edit a staff member', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $staff = StaffMember::factory()->create([
        'name' => 'Original Name',
        'phone_number' => '1234567890',
    ]);

    $this->actingAs($admin);

    Livewire::test(\App\Livewire\Admin\StaffMembers\Index::class)
        ->call('openEditModal', $staff->id)
        ->set('name', 'Updated Name')
        ->set('phoneNumber', '9876543210')
        ->call('updateStaffMember')
        ->assertHasNoErrors();

    $staff->refresh();
    expect($staff->name)->toBe('Updated Name')
        ->and($staff->phone_number)->toBe('9876543210');
});

it('allows admin to toggle staff member active status', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $staff = StaffMember::factory()->create(['is_active' => true]);

    $this->actingAs($admin);

    Livewire::test(\App\Livewire\Admin\StaffMembers\Index::class)
        ->call('toggleActive', $staff->id);

    $staff->refresh();
    expect($staff->is_active)->toBeFalse();
});

it('allows admin to delete a staff member', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $staff = StaffMember::factory()->create();

    $this->actingAs($admin);

    Livewire::test(\App\Livewire\Admin\StaffMembers\Index::class)
        ->call('confirmDelete', $staff->id)
        ->call('deleteStaffMember');

    expect(StaffMember::where('id', $staff->id)->exists())->toBeFalse();
});

it('filters staff members by search query', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $staff1 = StaffMember::factory()->create(['name' => 'John Doe']);
    $staff2 = StaffMember::factory()->create(['name' => 'Jane Smith']);

    $this->actingAs($admin);

    Livewire::test(\App\Livewire\Admin\StaffMembers\Index::class)
        ->set('search', 'John')
        ->assertSee('John Doe')
        ->assertDontSee('Jane Smith');
});

it('validates required fields when adding staff member', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin);

    Livewire::test(\App\Livewire\Admin\StaffMembers\Index::class)
        ->call('openAddModal')
        ->set('name', '')
        ->set('phoneCode', '')
        ->set('phoneNumber', '')
        ->call('addStaffMember')
        ->assertHasErrors(['name', 'phoneNumber']);
});
