<?php

namespace App\Livewire\Admin\Content;

use App\Models\FAQ;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class FaqIndex extends Component
{
    use WithPagination;

    public bool   $showForm  = false;
    public ?int   $editId    = null;
    public string $question  = '';
    public string $answer    = '';
    public string $category  = '';
    public bool   $isActive  = true;

    protected function rules(): array
    {
        return [
            'question' => 'required|string|max:500',
            'answer'   => 'required|string',
            'category' => 'nullable|string|max:100',
            'isActive' => 'boolean',
        ];
    }

    public function openCreate(): void
    {
        $this->authorize('content.create');
        $this->editId   = null;
        $this->question = '';
        $this->answer   = '';
        $this->category = '';
        $this->isActive = true;
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $this->authorize('content.update');
        $faq = FAQ::findOrFail($id);
        $this->editId   = $id;
        $this->question = $faq->question;
        $this->answer   = $faq->answer ?? '';
        $this->category = $faq->category ?? '';
        $this->isActive = (bool) ($faq->is_active ?? true);
        $this->showForm = true;
    }

    public function save(): void
    {
        $data = $this->validate();

        $payload = [
            'question'  => $data['question'],
            'answer'    => $data['answer'],
            'category'  => $data['category'] ?: null,
            'is_active' => $data['isActive'],
        ];

        if ($this->editId) {
            $this->authorize('content.update');
            FAQ::findOrFail($this->editId)->update($payload);
            session()->flash('success', 'FAQ updated.');
        } else {
            $this->authorize('content.create');
            FAQ::create($payload);
            session()->flash('success', 'FAQ created.');
        }

        $this->showForm = false;
    }

    public function render()
    {
        return view('livewire.admin.content.faqs', [
            'faqs' => FAQ::orderBy('sort_order')->orderBy('id')->paginate(25),
        ])->title('FAQs');
    }
}
