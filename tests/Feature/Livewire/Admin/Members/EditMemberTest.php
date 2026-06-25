<?php

declare(strict_types=1);

use App\Livewire\Admin\Members\Show;
use App\Models\Member;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

it('adds a cell number to a member from the edit page', function () {
    $member = Member::factory()->create(['cell_no' => null]);

    Livewire::actingAs($this->admin)
        ->test(Show::class, ['member' => $member])
        ->set('cellNo', '9876543210')
        ->call('save')
        ->assertHasNoErrors();

    expect($member->fresh()->cell_no)->toBe('9876543210');
});

it('renders the edit page with the editable contact fields', function () {
    $member = Member::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.members.show', $member))
        ->assertOk()
        ->assertSeeLivewire(Show::class)
        ->assertSee('Cell No')
        ->assertSee('Save Changes');
});
