<?php

namespace App\Livewire\Admin\Widgets;

use App\AuditLog;
use Livewire\Component;

class PlatformAuditLogs extends Component
{
    public $logs = [];

    public function mount()
    {
        $this->logs = AuditLog::with('user')
            ->orderBy('created_at', 'desc')
            ->take(8)
            ->get();
    }

    public function render()
    {
        return view('livewire.admin.widgets.platform-audit-logs');
    }
}
