<?php

namespace App\Livewire\Admin\Businesses;

use App\Models\Business;
use App\Services\AuditService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class BusinessEdit extends Component
{
    public Business $business;
    public string $name        = '';
    public string $code        = '';
    public string $description = '';
    public string $status      = 'active';

    public function mount(int $id): void
    {
        $this->authorize('business.update');
        $this->business    = Business::findOrFail($id);
        $this->name        = $this->business->name;
        $this->code        = $this->business->code ?? '';
        $this->description = $this->business->description ?? '';
        $this->status      = $this->business->status;
    }

    protected function rules(): array
    {
        return [
            'name'        => 'required|string|max:255',
            'code'        => "required|string|max:50|unique:businesses,code,{$this->business->id}",
            'description' => 'nullable|string|max:1000',
            'status'      => 'required|in:active,inactive',
        ];
    }

    public function save(): void
    {
        $this->authorize('business.update');
        $data     = $this->validate();
        $original = $this->business->toArray();

        $this->business->update($data);
        AuditService::logUpdate('App\\Models\\Business', $this->business, $original);

        session()->flash('success', 'Business updated successfully.');
        $this->redirect(route('admin.businesses.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.businesses.edit')->title('Edit Business');
    }
}
