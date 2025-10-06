<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;

class StallSelector extends Component
{
    public array $selectedStalls = [];

    public function mount(array $selectedStalls = []): void
    {
        $this->selectedStalls = $selectedStalls;
    }

    #[On('clear-stalls')]
    public function clearStalls(): void
    {
        $this->selectedStalls = [];
        $this->dispatch('stalls-selected', selectedStalls: $this->selectedStalls);
    }

    #[On('restore-stalls')]
    public function restoreStalls(array $selectedStalls): void
    {
        $this->selectedStalls = $selectedStalls;
        $this->dispatch('stalls-restored');
    }

    public function toggleStall(string $stallNumber): void
    {
        if (in_array($stallNumber, $this->selectedStalls)) {
            $this->selectedStalls = array_values(
                array_filter($this->selectedStalls, fn ($stall) => $stall !== $stallNumber)
            );
        } else {
            $this->selectedStalls[] = $stallNumber;
        }

        $this->dispatch('stalls-selected', selectedStalls: $this->selectedStalls);
    }

    public function render()
    {
        return view('livewire.stall-selector');
    }
}
