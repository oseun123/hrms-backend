<?php
// diag.php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\DB;

$output = "DATABASE CHECK AT " . date('Y-m-d H:i:s') . "\n\n";

$columns = DB::select("DESCRIBE users");
foreach ($columns as $col) {
    $output .= "Field: " . $col->Field . " | Type: " . $col->Type . "\n";
}

$output .= "\nUSER DATA:\n";
$users = User::all();
foreach ($users as $user) {
    $output .= "Email: " . $user->email . " | Last: " . ($user->last_login ?? 'NULL') . " | Prev: " . ($user->previous_login ?? 'NULL') . "\n";
}

file_put_contents('diag_output.txt', $output);
echo "Diagnostic complete. Check diag_output.txt\n";
