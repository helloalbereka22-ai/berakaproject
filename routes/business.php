<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'business.context'])->prefix('business')->name('business.')->group(function () {
    // Business Manager CRM Routes
    Route::get('/customers', \App\Livewire\Business\Crm\CustomerDirectory::class)->name('customers');
    Route::get('/customers/{id}', \App\Livewire\Business\Crm\CustomerProfile::class)->name('customers.show');
    
    // Additional business routes (Products, Manufacturing, Marketing) will go here
});
