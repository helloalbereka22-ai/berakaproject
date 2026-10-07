<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Livewire\Livewire;

try {
    Livewire::test(\App\Livewire\Public\Login::class)
        ->call('login', 'admin@albareka.com', 'password')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.dashboard'));
        
    echo "TEST PASSED!\n";
} catch (\Throwable $e) {
    echo "TEST FAILED: " . $e->getMessage() . "\n";
}
