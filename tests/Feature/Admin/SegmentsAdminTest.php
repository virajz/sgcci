<?php

use App\Livewire\Admin\Segments\Index;
use App\Livewire\Admin\Segments\SubSegments;
use App\Models\Segment;
use App\Models\SubSegment;
use App\Models\User;
use Livewire\Livewire;

it('admin can add a segment', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('openAddModal')
        ->set('name', 'Test Segment')
        ->set('sortOrder', 5)
        ->set('isActive', true)
        ->call('addSegment')
        ->assertHasNoErrors();

    $segment = Segment::where('name', 'Test Segment')->firstOrFail();
    expect($segment->sort_order)->toBe(5);
    expect($segment->is_active)->toBeTrue();
});

it('admin cannot add a duplicate segment name', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Segment::create(['name' => 'Already Exists', 'sort_order' => 0, 'is_active' => true]);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('openAddModal')
        ->set('name', 'Already Exists')
        ->call('addSegment')
        ->assertHasErrors(['name']);
});

it('admin can edit and toggle a segment', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $segment = Segment::create(['name' => 'Old Name', 'sort_order' => 1, 'is_active' => true]);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('openEditModal', $segment->id)
        ->set('name', 'New Name')
        ->set('sortOrder', 9)
        ->call('updateSegment')
        ->assertHasNoErrors();

    expect($segment->fresh()->name)->toBe('New Name');
    expect($segment->fresh()->sort_order)->toBe(9);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('toggleActive', $segment->id);

    expect($segment->fresh()->is_active)->toBeFalse();
});

it('admin can delete a segment which cascades to sub-segments', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $segment = Segment::create(['name' => 'Doomed', 'sort_order' => 0, 'is_active' => true]);
    SubSegment::create(['segment_id' => $segment->id, 'name' => 'A', 'sort_order' => 0, 'is_active' => true]);
    SubSegment::create(['segment_id' => $segment->id, 'name' => 'B', 'sort_order' => 1, 'is_active' => true]);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('confirmDelete', $segment->id)
        ->call('deleteSegment');

    expect(Segment::find($segment->id))->toBeNull();
    expect(SubSegment::where('segment_id', $segment->id)->count())->toBe(0);
});

it('admin can add a sub-segment scoped to its segment', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $segment = Segment::create(['name' => 'Parent', 'sort_order' => 0, 'is_active' => true]);

    Livewire::actingAs($admin)
        ->test(SubSegments::class, ['segment' => $segment])
        ->call('openAddModal')
        ->set('name', 'Child A')
        ->set('sortOrder', 1)
        ->set('isActive', true)
        ->call('addSubSegment')
        ->assertHasNoErrors();

    expect(SubSegment::where('segment_id', $segment->id)->where('name', 'Child A')->exists())->toBeTrue();
});

it('rejects duplicate sub-segment names within the same segment', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $segment = Segment::create(['name' => 'Parent', 'sort_order' => 0, 'is_active' => true]);
    SubSegment::create(['segment_id' => $segment->id, 'name' => 'Dup', 'sort_order' => 0, 'is_active' => true]);

    Livewire::actingAs($admin)
        ->test(SubSegments::class, ['segment' => $segment])
        ->call('openAddModal')
        ->set('name', 'Dup')
        ->call('addSubSegment')
        ->assertHasErrors(['name']);
});

it('allows the same sub-segment name across different segments', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $segmentA = Segment::create(['name' => 'A', 'sort_order' => 0, 'is_active' => true]);
    $segmentB = Segment::create(['name' => 'B', 'sort_order' => 1, 'is_active' => true]);
    SubSegment::create(['segment_id' => $segmentA->id, 'name' => 'Exporters', 'sort_order' => 0, 'is_active' => true]);

    Livewire::actingAs($admin)
        ->test(SubSegments::class, ['segment' => $segmentB])
        ->call('openAddModal')
        ->set('name', 'Exporters')
        ->call('addSubSegment')
        ->assertHasNoErrors();

    expect(SubSegment::where('segment_id', $segmentB->id)->where('name', 'Exporters')->exists())->toBeTrue();
});

it('non-admin users cannot reach the segments page', function () {
    $user = User::factory()->create(['role' => 'exhibitor']);

    $this->actingAs($user)
        ->get(route('admin.segments.index'))
        ->assertForbidden();
});
