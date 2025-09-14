<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Payment Data Debug ===\n";

$payment = \DB::table('payments', 'ap')
    ->select(\DB::raw('MAX(ap.id) as apId'), 'ap.payable_id as post_id')
    ->where('ap.payable_type', 'LIKE', '%Post')
    ->where('period_start', '<=', now())
    ->where('period_end', '>=', now())
    ->whereNull('canceled_at')
    ->whereNull('refunded_at')
    ->where('ap.active', 1)
    ->groupBy('ap.payable_id')
    ->first();

echo "Payment result:\n";
print_r($payment);

// Check which post this payment belongs to
if ($payment) {
    echo "\nPost with this payment:\n";
    $post = \App\Models\Post::find($payment->post_id);
    if ($post) {
        echo "Post ID: {$post->id}\n";
        echo "Post title: {$post->title}\n";
        echo "Post country: {$post->country_code}\n";
        echo "Post featured: {$post->featured}\n";
        echo "Post archived_at: " . ($post->archived_at ?? 'null') . "\n";
        echo "Post payment_id: " . ($post->payment_id ?? 'null') . "\n";
    } else {
        echo "Post not found!\n";
    }
}

// Let's see all payments for MW posts
echo "\n=== All active payments for MW posts ===\n";
$mwPayments = \DB::table('payments')
    ->join('posts', 'payments.payable_id', '=', 'posts.id')
    ->where('payments.payable_type', 'LIKE', '%Post')
    ->where('posts.country_code', 'MW')
    ->where('payments.active', 1)
    ->whereNull('payments.canceled_at')
    ->whereNull('payments.refunded_at')
    ->where('payments.period_start', '<=', now())
    ->where('payments.period_end', '>=', now())
    ->count();

echo "Active payments for MW posts: $mwPayments\n";

// Now let's test the actual join that's happening in the search
echo "\n=== Testing the actual LEFT JOIN ===\n";

$query = \App\Models\Post::query()
    ->select('posts.*')
    ->inCountry('MW')
    ->verified()
    ->unarchived();

$paymentBuilder = \DB::table('payments', 'ap')
    ->select(\DB::raw('MAX(ap.id) as apId'), 'ap.payable_id as post_id')
    ->where('ap.payable_type', 'LIKE', '%Post')
    ->where('period_start', '<=', now())
    ->where('period_end', '>=', now())
    ->whereNull('canceled_at')
    ->whereNull('refunded_at')
    ->where('ap.active', 1)
    ->groupBy('ap.payable_id');

$query->leftJoinSub($paymentBuilder, 'tmpAp', function ($join) {
    $join->on('tmpAp.post_id', '=', 'posts.id')->where('featured', 1);
});

echo "Query with LEFT JOIN count: " . $query->count() . "\n";
echo "Query with LEFT JOIN SQL:\n" . $query->toSql() . "\n";