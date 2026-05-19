<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Exhibition;
use App\Services\CurrentExhibition;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ExhibitionSplash extends Component
{
    public string $selectedId = '';

    public bool $open = false;

    public string $returnUrl = '';

    public function mount(): void
    {
        $latest = Exhibition::query()->orderByDesc('start_date')->value('id');
        $this->selectedId = (string) ($latest ?? '');
        $this->open = $this->shouldShow();
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
            ->get(['id', 'title', 'start_date', 'end_date', 'logo_path']);
    }

    public function shouldShow(): bool
    {
        $user = auth()->user();

        if (! $user || ! ($user->isAdmin() || $user->isFrontDesk())) {
            return false;
        }

        if (CurrentExhibition::isSelected()) {
            return false;
        }

        return Exhibition::query()->exists();
    }

    public function confirm(): void
    {
        $this->validate([
            'selectedId' => ['required', 'integer', 'exists:exhibitions,id'],
        ]);

        CurrentExhibition::set((int) $this->selectedId);
        $this->open = false;

        $this->redirect($this->returnUrl ?: route('dashboard'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.exhibition-splash');
    }
}
