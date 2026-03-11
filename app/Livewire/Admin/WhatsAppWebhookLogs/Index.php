<?php

declare(strict_types=1);

namespace App\Livewire\Admin\WhatsAppWebhookLogs;

use App\Models\WhatsAppWebhookLog;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(): \Illuminate\View\View
    {
        $logs = WhatsAppWebhookLog::query()
            ->when($this->search, function ($q) {
                $term = strtolower($this->search);
                $q->where(function ($q2) use ($term) {
                    $q2->whereRaw('LOWER(ip) LIKE ?', ["%{$term}%"])
                        ->orWhereRaw('LOWER(path) LIKE ?', ["%{$term}%"])
                        ->orWhereRaw('LOWER(method) LIKE ?', ["%{$term}%"]);
                });
            })
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('livewire.admin.whats-app-webhook-logs.index', [
            'logs' => $logs,
        ]);
    }
}
