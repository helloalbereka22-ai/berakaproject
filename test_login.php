<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Http\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

try {
    $request = \Illuminate\Http\Request::create('/login', 'POST');
    $app->instance('request', $request);

    $email = 'superadmin@albareka.com';
    $password = 'SuperAdmin@1234';

    $user = User::where('email', $email)->first();
    echo "--- User Details ---\n";
    if ($user) {
        echo "Found User ID: " . $user->id . "\n";
        echo "Email: " . $user->email . "\n";
        echo "Status: " . $user->status . "\n";
        
        // Manual Hash Check
        $hashMatch = Hash::check($password, $user->getAuthPassword());
        echo "Manual Hash Check ('$password'): " . ($hashMatch ? "MATCH" : "NO MATCH") . "\n";
    } else {
        echo "USER NOT FOUND in DB!\n";
    }

    echo "\n--- Auth::attempt ---\n";
    $credentials = ['email' => $email, 'password' => $password];
    
    // Auth::attempt automatically uses the default guard ('web' -> users provider)
    $attemptResult = Auth::attempt($credentials);
    echo "Auth::attempt result: " . ($attemptResult ? "SUCCESS" : "FAILED") . "\n";

    if ($attemptResult) {
        echo "Logged in user ID: " . Auth::id() . "\n";
    }

} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
