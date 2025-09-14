<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

echo "=== Job Description Fix Script ===" . PHP_EOL;
echo "This script will help restore missing job descriptions." . PHP_EOL;
echo PHP_EOL;

try {
    // Check current status
    $totalPosts = \App\Models\Post::count();
    $postsWithoutDesc = \App\Models\Post::where('description', '')->count();

    echo "Current Status:" . PHP_EOL;
    echo "- Total posts: {$totalPosts}" . PHP_EOL;
    echo "- Posts without description: {$postsWithoutDesc}" . PHP_EOL;
    echo "- Posts with description: " . ($totalPosts - $postsWithoutDesc) . PHP_EOL;
    echo PHP_EOL;

    if ($postsWithoutDesc > 0) {
        echo "ISSUE IDENTIFIED:" . PHP_EOL;
        echo "All job posts are missing their descriptions. This indicates a data migration issue." . PHP_EOL;
        echo PHP_EOL;

        echo "POSSIBLE SOLUTIONS:" . PHP_EOL;
        echo "1. Re-import data from the original source database" . PHP_EOL;
        echo "2. Check if descriptions exist in a different field or table" . PHP_EOL;
        echo "3. Use API integration to fetch missing job descriptions" . PHP_EOL;
        echo "4. Create a bulk update process with proper data source" . PHP_EOL;
        echo PHP_EOL;

        echo "RECOMMENDED IMMEDIATE ACTION:" . PHP_EOL;
        echo "Check the original database backup/export and ensure the 'description' field" . PHP_EOL;
        echo "is properly mapped during the import process." . PHP_EOL;
        echo PHP_EOL;

        // Check if there's any pattern in the data that might help
        echo "DATA ANALYSIS:" . PHP_EOL;
        $samplePosts = \App\Models\Post::limit(10)->get();
        foreach ($samplePosts as $post) {
            echo "ID: {$post->id}" . PHP_EOL;
            echo "  Title: {$post->title}" . PHP_EOL;
            echo "  Company: {$post->company_name}" . PHP_EOL;
            echo "  Company Desc: " . (strlen($post->company_description) > 0 ? "Has content (" . strlen($post->company_description) . " chars)" : "Empty") . PHP_EOL;
            echo "  Job Desc: " . (strlen($post->description) > 0 ? "Has content (" . strlen($post->description) . " chars)" : "Empty") . PHP_EOL;
            echo "  Created: {$post->created_at}" . PHP_EOL;
            echo "  ---" . PHP_EOL;
        }
    } else {
        echo "No issues found - all posts have descriptions!" . PHP_EOL;
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}