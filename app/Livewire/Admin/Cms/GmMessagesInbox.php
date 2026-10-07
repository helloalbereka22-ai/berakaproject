<?php

namespace App\Livewire\Admin\Cms;

use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class GmMessagesInbox extends Component
{
    use WithPagination;

    public string $search        = '';
    public ?int   $viewingId     = null;
    public bool   $confirmDelete = false;
    public ?int   $deleteId      = null;

    public function updatedSearch(): void { $this->resetPage(); }

    public function viewMessage(int $id): void  { $this->viewingId = $id; }
    public function closeMessage(): void         { $this->viewingId = null; }

    public function showDeleteConfirm(int $id): void
    {
        $this->deleteId      = $id;
        $this->confirmDelete = true;
    }

    public function deleteMessage(): void
    {
        if ($this->deleteId) {
            DB::table('contact_messages')->where('id', $this->deleteId)->delete();
        }
        $this->confirmDelete = false;
        $this->deleteId      = null;
        $this->viewingId     = null;
        $this->resetPage();
    }

    public function render()
    {
        $query = DB::table('contact_messages')
            ->when($this->search, function ($q) {
                $term = '%' . $this->search . '%';
                $q->where(function ($sq) use ($term) {
                    $sq->where('name',    'like', $term)
                       ->orWhere('email',   'like', $term)
                       ->orWhere('subject', 'like', $term)
                       ->orWhere('message', 'like', $term);
                });
            })
            ->orderByDesc('created_at');

        $total    = DB::table('contact_messages')->count();
        $messages = $query->paginate(20);

        $viewing = $this->viewingId
            ? DB::table('contact_messages')->where('id', $this->viewingId)->first()
            : null;

        return view('livewire.admin.cms.gm-messages-inbox', compact('messages', 'viewing', 'total'))
            ->title('Customer Messages');
    }
}
