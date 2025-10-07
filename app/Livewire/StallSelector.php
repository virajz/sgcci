<?php

namespace App\Livewire;

use App\Models\Booking;
use Livewire\Attributes\On;
use Livewire\Component;

class StallSelector extends Component
{
    public array $selectedStalls = [];

    public ?int $exhibitionId = null;

    public function mount(array $selectedStalls = [], ?int $exhibitionId = null): void
    {
        $this->selectedStalls = $selectedStalls;
        $this->exhibitionId = $exhibitionId;
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

    public function getBookedStallsProperty(): array
    {
        if (! $this->exhibitionId) {
            return [];
        }

        return Booking::where('exhibition_id', $this->exhibitionId)
            ->get()
            ->flatMap(function ($booking) {
                return collect($booking->selected_stalls)->map(function ($stall) use ($booking) {
                    return [
                        'stall_number' => $stall,
                        'status' => $booking->status,
                    ];
                });
            })
            ->groupBy('stall_number')
            ->map(fn ($stalls) => $stalls->first()['status'])
            ->toArray();
    }

    public function render()
    {
        return view('livewire.stall-selector', [
            'bookedStalls' => $this->bookedStalls,
        ]);
    }
}
