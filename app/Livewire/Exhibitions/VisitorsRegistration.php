<?php

namespace App\Livewire\Exhibitions;

use App\Models\Exhibition;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.front')]
class VisitorsRegistration extends Component
{
    public Exhibition $exhibition;

    public function mount(Exhibition $exhibition): void
    {
        $this->exhibition = $exhibition;
    }

    public function render()
    {
        return view('livewire.exhibitions.visitors-registration');
    }
}
