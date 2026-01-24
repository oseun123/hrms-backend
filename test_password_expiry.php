<?php

// Quick test script to debug password expiry
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Preference\Preference;
use Carbon\Carbon;

echo "=== Password Expiry Debug ===\n\n";

// Check preference
$pref = Preference::where('tenant_id', 1)
    ->whereNull('user_id')
    ->where('category', 'security_policy')
    ->where('key', 'password_expiry_days')
    ->first();

if ($pref) {
    echo "Preference found:\n";
    echo "  Value: " . var_export($pref->value, true) . "\n";
    echo "  Type: " . gettype($pref->value) . "\n";
    echo "  As Integer: " . (int)$pref->value . "\n";
} else {
    echo "Preference NOT found!\n";
}

echo "\n";

// Check user 3
$user = \App\Models\User::find(3);
if ($user) {
    echo "User 3 (Jane Marie Smith):\n";
    echo "  Email: {$user->email}\n";
    echo "  Password Changed At: " . ($user->password_changed_at ?? 'NULL') . "\n";

    if ($user->password_changed_at) {
        $daysSince = now()->diffInDays($user->password_changed_at);
        echo "  Days Since Change: {$daysSince}\n";

        $expiryDays = Preference::getValue('security_policy', 'password_expiry_days', 1, null, 0);
        echo "  Expiry Days Setting: {$expiryDays} (type: " . gettype($expiryDays) . ")\n";
        echo "  Should Expire: " . ($daysSince >= $expiryDays && $expiryDays > 0 ? 'YES' : 'NO') . "\n";
    }
}

echo "\n=== End Debug ===\n";
