<?php

use App\Livewire\Admin\Exhibitions\Index;
use App\Models\Exhibition;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
});

it('saves logo, pass background and coordinates when adding an exhibition', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $logo = UploadedFile::fake()->image('logo.png', 200, 200);
    $background = UploadedFile::fake()->image('pass.jpg', 1080, 1920);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('openAddModal')
        ->set('title', 'Tech Expo 2027')
        ->set('description', 'Annual tech exhibition.')
        ->set('startDate', '2027-01-01')
        ->set('endDate', '2027-01-05')
        ->set('entryType', 'free')
        ->set('logoUpload', $logo)
        ->set('passBackgroundUpload', $background)
        ->set('passQrX', 295)
        ->set('passQrY', 619)
        ->set('passQrSize', 460)
        ->set('passNameX', 540)
        ->set('passNameY', 1390)
        ->set('passNameColor', '#ff8800')
        ->call('addExhibition')
        ->assertHasNoErrors();

    $exhibition = Exhibition::where('title', 'Tech Expo 2027')->firstOrFail();

    expect($exhibition->logo_path)->not->toBeNull();
    expect($exhibition->pass_background_path)->not->toBeNull();
    expect($exhibition->pass_qr_x)->toBe(295);
    expect($exhibition->pass_qr_y)->toBe(619);
    expect($exhibition->pass_qr_size)->toBe(460);
    expect($exhibition->pass_name_x)->toBe(540);
    expect($exhibition->pass_name_y)->toBe(1390);
    expect($exhibition->pass_name_color)->toBe('#ff8800');
    expect($exhibition->hasCustomPass())->toBeTrue();

    Storage::disk('public')->assertExists($exhibition->logo_path);
    Storage::disk('public')->assertExists($exhibition->pass_background_path);
});

it('replaces the existing logo when a new one is uploaded during edit', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $oldLogo = UploadedFile::fake()->image('old.png');
    $oldPath = $oldLogo->storeAs('exhibitions/seed', 'old.png', 'public');

    $exhibition = Exhibition::factory()->create([
        'logo_path' => $oldPath,
        'start_date' => '2027-01-01',
        'end_date' => '2027-01-05',
    ]);

    Storage::disk('public')->assertExists($oldPath);

    $newLogo = UploadedFile::fake()->image('new.png');

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('openEditModal', $exhibition->id)
        ->set('logoUpload', $newLogo)
        ->call('updateExhibition')
        ->assertHasNoErrors();

    $exhibition->refresh();

    expect($exhibition->logo_path)->not->toBe($oldPath);
    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($exhibition->logo_path);
});

it('removes the pass background and clears coordinates', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $bg = UploadedFile::fake()->image('bg.jpg');
    $path = $bg->storeAs('exhibitions/seed', 'bg.jpg', 'public');

    $exhibition = Exhibition::factory()->create([
        'pass_background_path' => $path,
        'pass_qr_x' => 100,
        'pass_qr_y' => 100,
        'pass_qr_size' => 200,
        'pass_name_x' => 200,
        'pass_name_y' => 400,
    ]);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('openEditModal', $exhibition->id)
        ->call('removePassBackground');

    $exhibition->refresh();

    expect($exhibition->pass_background_path)->toBeNull();
    expect($exhibition->pass_qr_x)->toBeNull();
    expect($exhibition->pass_qr_y)->toBeNull();
    expect($exhibition->pass_qr_size)->toBeNull();
    expect($exhibition->pass_name_x)->toBeNull();
    expect($exhibition->pass_name_y)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

it('reports hasCustomPass() correctly', function () {
    $exhibition = Exhibition::factory()->create();
    expect($exhibition->hasCustomPass())->toBeFalse();

    $exhibition->update([
        'pass_background_path' => 'something.jpg',
        'pass_qr_x' => 10,
        'pass_qr_y' => 10,
        'pass_qr_size' => 100,
    ]);

    expect($exhibition->fresh()->hasCustomPass())->toBeTrue();
});
