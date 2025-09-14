<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Post;
use App\Models\Payment;
use App\Models\Package;
use Illuminate\Support\Carbon;
use App\Helpers\Date;

echo "=== Payment Relation Debug ===\n";

// Simulate the search conditions
$op = 'search';
$cat = null;  // No category selected
$city = null; // No city selected

// Check premium settings
$premium_first = config('settings.listings_list.premium_first');
$premium_first_category = config('settings.listings_list.premium_first_category');
$premium_first_location = config('settings.listings_list.premium_first_location');

echo "Premium settings:\n";
echo "- premium_first: " . ($premium_first ?? 'null') . "\n";
echo "- premium_first_category: " . ($premium_first_category ?? 'null') . "\n";
echo "- premium_first_location: " . ($premium_first_location ?? 'null') . "\n";
echo "- cat: " . ($cat ?? 'null') . "\n";
echo "- city: " . ($city ?? 'null') . "\n";

// Determine which path will be taken
$displayPremiumFirst = (
    ($premium_first == '1' && empty($cat) && empty($city))
    || ($premium_first_category == '1' && !empty($cat))
    || ($premium_first_location == '1' && !empty($city))
);

echo "displayPremiumFirst: " . ($displayPremiumFirst ? 'true' : 'false') . "\n";

if ($displayPremiumFirst) {
    echo "Will call setRelationForPremiumFirst()\n";
} else {
    echo "Will call setRelationForLatest()\n";
}

// Test the actual payment join query
echo "\n=== Testing Payment Builder ===\n";

$today = Carbon::now(Date::getAppTimeZone());
$paymentsTable = (new Payment())->getTable();

echo "Today: $today\n";
echo "Payments table: $paymentsTable\n";

// Check if there are any payments at all
echo "Total payments: " . \DB::table($paymentsTable)->count() . "\n";
echo "Active payments: " . \DB::table($paymentsTable)->where('active', 1)->count() . "\n";
echo "Payment for posts: " . \DB::table($paymentsTable)->where('payable_type', 'LIKE', '%Post')->count() . "\n";

// Test the payment builder query manually
$paymentBuilder = \DB::table($paymentsTable, 'ap')
    ->select(\DB::raw('MAX(ap.id) as apId'), 'ap.payable_id as post_id')
    ->where('ap.payable_type', 'LIKE', '%Post')
    ->where('period_start', '<=', $today)
    ->where('period_end', '>=', $today)
    ->whereNull('canceled_at')
    ->whereNull('refunded_at')
    ->where('ap.active', 1)
    ->groupBy('ap.payable_id');

echo "Payment builder SQL: " . $paymentBuilder->toSql() . "\n";
echo "Payment builder count: " . $paymentBuilder->count() . "\n";