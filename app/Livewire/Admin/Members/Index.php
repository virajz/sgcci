<?php

namespace App\Livewire\Admin\Members;

use App\Models\Member;
use App\Services\CurrentExhibition;
use App\Services\ExhibitionPassSender;
use Flux\Flux;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showSendAllModal = false;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function sendWhatsApp(int $id): void
    {
        $exhibition = CurrentExhibition::model();

        if (! $exhibition) {
            Flux::toast(heading: 'Select an exhibition', variant: 'warning', text: 'Please select an exhibition before sending a pass.');

            return;
        }

        $member = Member::findOrFail($id);

        $sent = (new ExhibitionPassSender($exhibition))
            ->sendToContact($member->contact_name, $member->cell_no, $member->company, $member->city_a, 'member');

        if (! $sent) {
            Flux::toast(heading: 'No mobile number', variant: 'warning', text: "No cell number on file for {$member->contact_name}.");

            return;
        }

        Flux::toast(heading: 'WhatsApp Sent!', variant: 'success', text: "Pass sent to {$member->contact_name}.");
    }

    public function confirmSendAll(): void
    {
        if (! CurrentExhibition::isSelected()) {
            Flux::toast(heading: 'Select an exhibition', variant: 'warning', text: 'Please select an exhibition before sending passes.');

            return;
        }

        $this->showSendAllModal = true;
    }

    public function sendWhatsAppToAll(): void
    {
        $exhibition = CurrentExhibition::model();

        if (! $exhibition) {
            $this->showSendAllModal = false;
            Flux::toast(heading: 'Select an exhibition', variant: 'warning', text: 'Please select an exhibition before sending passes.');

            return;
        }

        $sender = new ExhibitionPassSender($exhibition);
        $sent = 0;
        $skipped = 0;

        Member::query()->chunkById(200, function ($members) use ($sender, &$sent, &$skipped): void {
            foreach ($members as $member) {
                $sender->sendToContact($member->contact_name, $member->cell_no, $member->company, $member->city_a, 'member')
                    ? $sent++
                    : $skipped++;
            }
        });

        $this->showSendAllModal = false;

        Flux::toast(
            heading: 'WhatsApp Sent!',
            variant: $sent > 0 ? 'success' : 'warning',
            text: $skipped > 0
                ? "Sent {$sent} passes. Skipped {$skipped} (no cell number)."
                : "Sent {$sent} passes."
        );
    }

    public function render(): View
    {
        $members = Member::query()
            ->when($this->search, function ($query) {
                $search = strtolower($this->search);
                $query->where(function ($q) use ($search) {
                    $q->whereRaw('LOWER(contact_name) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(membership_number) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(company) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('REPLACE(LOWER(cell_no), \' \', \'\') LIKE ?', ['%'.str_replace(' ', '', $search).'%']);
                });
            })
            ->orderBy('membership_number')
            ->paginate(20);

        return view('livewire.admin.members.index', [
            'members' => $members,
            'exhibitionSelected' => CurrentExhibition::isSelected(),
        ]);
    }
}
