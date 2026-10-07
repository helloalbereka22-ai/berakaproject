<?php
require 'vendor/autoload.php';
\\\ = require_once 'bootstrap/app.php';
\\\ = \\\->make('Illuminate\\Contracts\\Http\\Kernel');
\\\ = \\\->handle(\\\ = Illuminate\\Http\\Request::capture());
\\\ = \App\Models\Category::withoutGlobalScopes()->where('type', 'oil')->orWhere('type', 'finished_product')->orderBy('name')->get();
echo count(\\\) . ' categories found:\n';
foreach(\\\ as \\\) { 
    echo \"ID: {\\\->id} | Name: {\\\->name} | Type: {\\\->type}\n\"; 
}
?>
