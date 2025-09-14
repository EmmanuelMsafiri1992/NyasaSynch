<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Post;
use App\Helpers\Search\PostQueries;

echo "=== Search Debug Information ===\n";

// Test regular query
$regularCount = Post::withoutGlobalScopes()->inCountry('MW')->count();
echo "1. Regular posts (withoutGlobalScopes + inCountry): $regularCount\n";

// Test with filters step by step
$step1 = Post::query()->inCountry('MW')->count();
echo "2. With inCountry only: $step1\n";

$step2 = Post::query()->inCountry('MW')->verified()->count();
echo "3. With inCountry + verified: $step2\n";

$step3 = Post::query()->inCountry('MW')->verified()->unarchived()->count();
echo "4. With inCountry + verified + unarchived: $step3\n";

// Test search class
echo "\n=== Testing PostQueries Class ===\n";
$input = [
    'op' => 'search',
    'perPage' => 12,
    'orderBy' => null,
];
$preSearch = [];

$postQueries = new PostQueries($input, $preSearch);
$result = $postQueries->fetch([]);

echo "PostQueries result count: " . count($result['posts']['data']) . "\n";
echo "PostQueries total: " . $result['posts']['meta']['total'] . "\n";

if (isset($result['posts']['meta']['total']) && $result['posts']['meta']['total'] == 0) {
    echo "\n=== DEBUG: The PostQueries is returning 0 results ===\n";
}