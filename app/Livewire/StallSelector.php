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
                    // Map statuses for UI display
                    $uiStatus = match ($booking->status->value) {
                        'payment_completed' => 'allotted',
                        'pending_approval', 'approved_by_admin', 'payment_pending', 'allotted' => 'reserved',
                        'rejected', 'expired', 'cancelled' => null, // These stalls are available again
                        default => null,
                    };

                    return [
                        'stall_number' => $stall,
                        'status' => $uiStatus,
                    ];
                });
            })
            ->filter(fn ($stall) => $stall['status'] !== null) // Remove stalls with null status
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
