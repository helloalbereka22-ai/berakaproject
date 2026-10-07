<?php

namespace App\Livewire\Admin\Businesses;

use App\Models\Business;
use App\Services\AuditService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class BusinessCreate extends Component
{
    public string $name        = '';
    public string $code        = '';
    public string $description = '';
    public string $status      = 'active';

    protected function rules(): array
    {
        return [
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|max:50|unique:businesses,code',
            'description' => 'nullable|string|max:1000',
            'status'      => 'required|in:active,inactive',
        ];
    }

    public function save(): void
    {
        $this->authorize('business.create');
        $data = $this->validate();

        $business = Business::create($data);
        AuditService::logCreate('App\\Models\\Business', $business);

        session()->flash('success', "Business '{$business->name}' created successfully.");
        $this->redirect(route('admin.businesses.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.businesses.create')->title('Create Business');
    }
}
