<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Checking Razorpay Account Keys...\n\n";

$account = \App\Models\AcquirerAccount::where('acquirer_name', 'LIKE', '%razorpay%')->first();

if (!$account) {
    echo "❌ No Razorpay account found!\n";
    exit(1);
}

echo "✅ Found account: {$account->acquirer_name} (ID: {$account->id})\n\n";

echo "Key Status:\n";
echo "  Additional Key 1: " . (empty($account->additional_key_1) ? "❌ EMPTY" : "✅ SET (" . strlen($account->additional_key_1) . " chars)") . "\n";
echo "  Secret Key: " . (empty($account->secret_key) ? "❌ EMPTY" : "✅ SET (" . strlen($account->secret_key) . " chars)") . "\n";
echo "  Additional Key 2: " . (empty($account->additional_key_2) ? "❌ EMPTY" : "✅ SET (" . strlen($account->additional_key_2) . " chars)") . "\n";
echo "  Salt: " . (empty($account->salt) ? "❌ EMPTY" : "✅ SET (" . strlen($account->salt) . " chars)") . "\n\n";

if (empty($account->additional_key_1) && empty($account->secret_key)) {
    echo "⚠️  WARNING: Both keys are empty!\n";
    echo "   You need to re-enter them in the edit form.\n";
} elseif (empty($account->additional_key_1)) {
    echo "⚠️  WARNING: Additional Key 1 (Razorpay Key ID) is empty!\n";
    echo "   Please enter your Razorpay Key ID in 'Additional Key 1' field.\n";
} elseif (empty($account->secret_key)) {
    echo "⚠️  WARNING: Secret Key (Razorpay Secret Key) is empty!\n";
    echo "   Please enter your Razorpay Secret Key in 'Secret Key' field.\n";
} else {
    echo "✅ Keys are present in database!\n";
    echo "   If they're not showing in the form, check browser console for errors.\n";
}

echo "\n";

