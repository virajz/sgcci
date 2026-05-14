<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Exhibition;
use App\Services\CurrentExhibition;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ExhibitionSelector extends Component
{
    public string $selectedId = '';

    public string $returnUrl = '';

    public function mount(): void
    {
        $this->selectedId = (string) (CurrentExhibition::id() ?? '');
        $this->returnUrl = url()->current();
    }

    /**
     * @return Collection<int, Exhibition>
     */
    #[Computed]
    public function exhibitions(): Collection
    {
        return Exhibition::query()
            ->orderByDesc('start_date')
            ->get(['id', 'title', 'logo_path']);
    }

    public function updatedSelectedId(string $value): void
    {
        if ($value === '') {
            return;
        }

        CurrentExhibition::set((int) $value);

        $this->redirect($this->returnUrl ?: route('dashboard'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.exhibition-selector');
    }
}
