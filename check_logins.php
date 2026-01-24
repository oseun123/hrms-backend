<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$users = User::all(['id', 'email', 'last_login', 'previous_login']);

foreach ($users as $user) {
    echo "User ID: {$user->id}\n";
    echo "Email: {$user->email}\n";
    echo "Last Login: " . ($user->last_login ?? 'NULL') . "\n";
    echo "Previous Login: " . ($user->previous_login ?? 'NULL') . "\n";
    echo "--------------------------\n";
}
