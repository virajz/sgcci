<?php

namespace App\Livewire\Exhibitions;

use App\Models\Exhibition;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('components.layouts.front')]
class Booking extends Component
{
    public Exhibition $exhibition;

    public string $brandName = '';

    public string $contactPerson = '';

    public string $phoneCode = '+91';

    public string $phoneNumber = '';

    public string $email = '';

    public string $city = 'Surat';

    public array $productProfile = [];

    public bool $hasExhibitedBefore = false;

    public array $participationYears = [];

    public bool $isSgcciMember = false;

    public string $membershipType = '';

    public array $selectedStalls = [];

    #[On('stalls-selected')]
    public function updateSelectedStalls(array $selectedStalls): void
    {
        $this->selectedStalls = $selectedStalls;
    }

    public function clearSelectedStalls(): void
    {
        $this->selectedStalls = [];
        $this->dispatch('clear-stalls');
    }

    public function mount(Exhibition $exhibition)
    {
        $this->exhibition = $exhibition;
    }

    public function render()
    {
        return view('livewire.exhibitions.booking', [
            'exhibition' => $this->exhibition,
        ]);
    }
}
