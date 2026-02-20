<?php

namespace App\Livewire\Admin\Visitors;

use App\Models\ExhibitionVisitor;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Show extends Component
{
    public ExhibitionVisitor $visitor;

    public bool $showDeleteModal = false;

    public function mount(ExhibitionVisitor $visitor): void
    {
        $this->visitor = $visitor->load('exhibition');
    }

    public function confirmDelete(): void
    {
        $this->showDeleteModal = true;
    }

    public function deleteVisitor(): void
    {
        $this->visitor->delete();

        Flux::toast(
            heading: 'Visitor Deleted!',
            variant: 'success',
            text: 'Visitor registration has been removed successfully.'
        );

        $this->redirect(route('admin.visitors.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.visitors.show');
    }
}
