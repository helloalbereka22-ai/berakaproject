<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// ── Inventory: check low-stock every 6 hours ──────────────────────────────────
Schedule::command('inventory:check-low-stock')
    ->everySixHours()
    ->withoutOverlapping()
    ->runInBackground();

