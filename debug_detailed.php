<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Post;

echo "=== Detailed Filter Debug ===\n";

// Start with basic query
$query = Post::query();
echo "1. Base query count: " . $query->count() . "\n";

// Apply inCountry
$query = Post::query();
$query->inCountry();
echo "2. After inCountry(): " . $query->count() . "\n";

// Apply verified
$query = Post::query();
$query->inCountry()->verified();
echo "3. After inCountry()->verified(): " . $query->count() . "\n";

// Apply unarchived
$query = Post::query();
$query->inCountry()->verified()->unarchived();
echo "4. After inCountry()->verified()->unarchived(): " . $query->count() . "\n";

// Check if review is required
$reviewRequired = config('settings.listing_form.listings_review_activation');
echo "5. Review activation setting: " . ($reviewRequired ? 'true' : 'false') . "\n";

if ($reviewRequired) {
    $query = Post::query();
    $query->inCountry()->verified()->unarchived()->reviewed();
    echo "6. After adding reviewed(): " . $query->count() . "\n";
}

// Check actual SQL being generated
$query = Post::query();
$query->inCountry()->verified()->unarchived();
if ($reviewRequired) {
    $query->reviewed();
}
echo "\n=== SQL Query ===\n";
echo $query->toSql() . "\n";

// Check bindings
echo "\n=== Bindings ===\n";
print_r($query->getBindings());

// Check what payment status should be
echo "\n=== Payment Status Check ===\n";
echo "Posts with NULL payment: " . Post::whereNull('payment_id')->count() . "\n";
echo "Posts with payment: " . Post::whereNotNull('payment_id')->count() . "\n";

// Check actual payment relation data
$postsWithPayment = Post::with('payment')->inCountry('MW')->verified()->unarchived()->take(5)->get();
echo "\nSample posts payment status:\n";
foreach ($postsWithPayment as $post) {
    echo "Post ID {$post->id}: payment_id=" . ($post->payment_id ?? 'NULL') .
         ", payment active=" . ($post->payment ? $post->payment->active : 'N/A') . "\n";
}