<?php

/**
 * Script to replace all instances of JobClass with Nyasajob
 * This preserves system functionality while updating branding
 */

echo "Starting JobClass to Nyasajob replacement...\n";

// Define replacement patterns
$replacements = [
    'JobClass' => 'Nyasajob',
    'Job Class' => 'Nyasajob',
    'job class' => 'Nyasajob',
    'jobclass' => 'nyasajob',
    'JOBCLASS' => 'NYASAJOB'
];

// Define directories to process
$directories = [
    'app/',
    'config/',
    'resources/',
    'lang/',
    'extras/',
    '.scribe/',
    'storage/database/'
];

// File extensions to process
$extensions = ['php', 'blade.php', 'js', 'css', 'md', 'html', 'txt', 'yaml', 'json'];

$totalFiles = 0;
$modifiedFiles = 0;

foreach ($directories as $directory) {
    if (!is_dir($directory)) {
        echo "Directory not found: $directory\n";
        continue;
    }

    echo "Processing directory: $directory\n";

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if (!$file->isFile()) continue;

        $extension = pathinfo($file->getFilename(), PATHINFO_EXTENSION);
        if (!in_array($extension, $extensions) && !str_ends_with($file->getFilename(), '.blade.php')) {
            continue;
        }

        $filePath = $file->getPathname();
        $totalFiles++;

        // Read file content
        $content = file_get_contents($filePath);
        if ($content === false) {
            echo "Could not read file: $filePath\n";
            continue;
        }

        $originalContent = $content;

        // Apply replacements
        foreach ($replacements as $search => $replace) {
            $content = str_replace($search, $replace, $content);
        }

        // Only write if content changed
        if ($content !== $originalContent) {
            if (file_put_contents($filePath, $content) !== false) {
                echo "✓ Modified: $filePath\n";
                $modifiedFiles++;
            } else {
                echo "✗ Failed to write: $filePath\n";
            }
        }
    }
}

echo "\n=== Replacement Summary ===\n";
echo "Total files processed: $totalFiles\n";
echo "Files modified: $modifiedFiles\n";
echo "\nJobClass to Nyasajob replacement completed!\n";

// Clear any caches that might be affected
echo "\nClearing application caches...\n";
if (file_exists('bootstrap/cache/config.php')) {
    unlink('bootstrap/cache/config.php');
    echo "✓ Cleared config cache\n";
}
if (file_exists('bootstrap/cache/routes-v7.php')) {
    unlink('bootstrap/cache/routes-v7.php');
    echo "✓ Cleared routes cache\n";
}
if (file_exists('bootstrap/cache/services.php')) {
    unlink('bootstrap/cache/services.php');
    echo "✓ Cleared services cache\n";
}

echo "Cache clearing completed!\n";
echo "\nAll replacements finished successfully!\n";