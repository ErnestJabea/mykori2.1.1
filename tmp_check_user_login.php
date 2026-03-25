<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('email', 'ernestjabs@gmail.com')->first();
if ($user) {
    echo "User Found:\n";
    echo "ID: " . $user->id . "\n";
    echo "Name: " . $user->name . "\n";
    echo "Email: " . $user->email . "\n";
    echo "Role ID: " . $user->role_id . "\n";
    echo "Created At: " . $user->created_at . "\n";
} else {
    echo "User Not Found.\n";
}
