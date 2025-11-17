<?php

namespace App\Livewire\Exhibitions;

use App\Models\Exhibition;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.front')]
class ProductProfileSelection extends Component
{
    public Exhibition $exhibition;

    public array $selectedProfiles = [];

    public function mount(Exhibition $exhibition): void
    {
        $this->exhibition = $exhibition;
    }

    public function continue(): void
    {
        $this->validate([
            'selectedProfiles' => ['required', 'array', 'min:1'],
            'selectedProfiles.*' => ['string', 'in:4-wheelers,2-wheelers,automobile-ancillaries'],
        ]);

        session(['product_profiles' => $this->selectedProfiles]);

        $this->redirect(route('exhibitions.booking.show', $this->exhibition), navigate: true);
    }

    public function render()
    {
        return view('livewire.exhibitions.product-profile-selection');
    }
}
