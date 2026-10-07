<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Move ALL oil/ml products to finished_product type so they go to Manufacturing page
$moved = DB::table('products')
    ->where(function($q) {
        $q->where('name', 'like', '%ml%')
          ->orWhere('name', 'like', '%telba%')
          ->orWhere('name', 'like', '%azmud%')
          ->orWhere('name', 'like', '%azmut%')
          ->orWhere('name', 'like', '%selit%')
          ->orWhere('name', 'like', '%bahrzef%')
          ->orWhere('name', 'like', '%tukur%')
          ->orWhere('name', 'like', '%zait%')
          ->orWhere('name', 'like', '%oil%')
          ->orWhere('name', 'like', '%liter%');
    })
    ->update(['type' => 'finished_product']);

echo "Moved {$moved} oil products to Manufacturing. <a href='/store/products'>Check Products Page</a>";
