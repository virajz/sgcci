<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Scans;

use App\Models\ExhibitionVisitor;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $entryFilter = '';

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->endOfDay()->format('Y-m-d');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function updatingEntryFilter(): void
    {
        $this->resetPage();
    }

    /** @return array<int, array{date: string, entries: int}> */
    public function getDailyScansProperty(): array
    {
        $rows = ExhibitionVisitor::query()
            ->whereNotNull('entered_at')
            ->where('entered_at', '>=', now()->subDays(13)->startOfDay())
            ->selectRaw('DATE(entered_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day')
            ->toArray();

        $result = [];
        for ($i = 13; $i >= 0; $i--) {
            $day = now()->subDays($i)->format('Y-m-d');
            $result[] = ['date' => $day, 'entries' => $rows[$day] ?? 0];
        }

        return $result;
    }

    /** @return array{total_entered: int, today: int, total_exited: int} */
    public function getScanStatsProperty(): array
    {
        return [
            'total_entered' => ExhibitionVisitor::whereNotNull('entered_at')->count(),
            'today' => ExhibitionVisitor::whereNotNull('entered_at')->whereDate('entered_at', today())->count(),
            'total_exited' => ExhibitionVisitor::whereNotNull('exited_at')->count(),
        ];
    }

    public function render(): \Illuminate\View\View
    {
        $query = ExhibitionVisitor::query()
            ->whereNotNull('entered_at')
            ->with('exhibition')
            ->when($this->search, function ($q) {
                $search = strtolower($this->search);
                $q->where(function ($q2) use ($search) {
                    $q2->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(company_name) LIKE ?', ["%{$search}%"])
                        ->orWhere('phone_number', 'like', "%{$search}%")
                        ->orWhereRaw('LOWER(registration_code) LIKE ?', ["%{$search}%"]);
                });
            })
            ->when($this->dateFrom, fn ($q) => $q->whereDate('entered_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('entered_at', '<=', $this->dateTo))
            ->when($this->entryFilter === 'inside', fn ($q) => $q->whereNull('exited_at'))
            ->when($this->entryFilter === 'exited', fn ($q) => $q->whereNotNull('exited_at'))
            ->orderByDesc('entered_at')
            ->paginate(20);

        return view('livewire.admin.scans.index', [
            'scans' => $query,
            'dailyScans' => $this->dailyScans,
            'scanStats' => $this->scanStats,
        ]);
    }
}
