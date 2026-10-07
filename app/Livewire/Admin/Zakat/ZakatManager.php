<?php

namespace App\Livewire\Admin\Zakat;

use App\Models\ZakatCycle;
use App\Services\ZakatService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class ZakatManager extends Component
{
    // ── Start cycle form ──────────────────────────────────────────────────
    public float  $nisabAmount  = 0;
    public float  $zakatRate    = 2.5;
    public string $startDate    = '';
    public string $startHijri   = '';

    // ── Payment form ──────────────────────────────────────────────────────
    public float  $paymentAmount    = 0;
    public string $paymentReference = '';
    public string $paymentNotes     = '';

    // ── UI state ──────────────────────────────────────────────────────────
    public bool $showStartForm   = false;
    public bool $showPayForm     = false;
    public bool $successMsg      = false;

    public function mount(): void
    {
        $this->startDate = now()->toDateString();
    }

    public function autoRefresh(ZakatService $svc): void
    {
        $cycle = $svc->currentCycle();
        if ($cycle) {
            $svc->refreshWealth($cycle);
        }
    }

    public function startCycle(ZakatService $svc): void
    {
        $this->validate([
            'nisabAmount' => 'required|numeric|min:1',
            'zakatRate'   => 'required|numeric|min:0.1|max:100',
            'startDate'   => 'required|date',
        ]);

        // Close any existing non-paid cycle first
        ZakatCycle::whereNotIn('status', [ZakatCycle::STATUS_PAID])->update(['status' => ZakatCycle::STATUS_PAID]);

        $svc->startCycle($this->nisabAmount, $this->zakatRate, $this->startDate, $this->startHijri);

        $this->showStartForm = false;
        $this->successMsg    = true;
        $this->dispatch('zakat-updated');
    }

    public function refreshWealth(ZakatService $svc): void
    {
        $cycle = $svc->currentCycle();
        if ($cycle) {
            $svc->refreshWealth($cycle);
        }
        $this->dispatch('zakat-updated');
    }

    public function openPayForm(ZakatService $svc): void
    {
        $cycle = $svc->currentCycle();
        if ($cycle) {
            $this->paymentAmount = (float)$cycle->zakat_due;
        }
        $this->showPayForm = true;
    }

    public function recordPayment(ZakatService $svc): void
    {
        $this->validate([
            'paymentAmount' => 'required|numeric|min:0.01',
        ]);

        $cycle = $svc->currentCycle();
        if (!$cycle) return;

        $svc->recordPayment($cycle, $this->paymentAmount, $this->paymentReference, $this->paymentNotes);

        // Immediately start new cycle with same settings
        $svc->startCycle(
            (float)$cycle->nisab_amount,
            (float)$cycle->zakat_rate,
            now()->toDateString()
        );

        $this->showPayForm    = false;
        $this->paymentAmount  = 0;
        $this->paymentReference = '';
        $this->paymentNotes   = '';
        $this->successMsg     = true;
        $this->dispatch('zakat-updated');
    }

    public function render(ZakatService $svc)
    {
        $summary = $svc->dashboardSummary();
        $history = ZakatCycle::orderByDesc('start_date')->get();

        return view('livewire.admin.zakat.zakat-manager', [
            'summary' => $summary,
            'history' => $history,
        ])->title('Zakat al-Mal');
    }
}
