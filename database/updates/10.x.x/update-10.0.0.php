<?php

use Illuminate\Support\Facades\File;

// ===| FILES |===
try {
	
	$oldLangBaseDir = str(resource_path('lang'))->finish('/')->toString();
	$newLangBaseDir = str(base_path('lang'))->finish('/')->toString();
	$oldLangDirsArray = array_filter(glob($oldLangBaseDir . '*'), 'is_dir');
	if (!empty($oldLangDirsArray)) {
		foreach ($oldLangDirsArray as $oldLangDir) {
			$newLangDir = $newLangBaseDir . basename($oldLangDir);
			if (!File::exists($newLangDir)) {
				File::moveDirectory($oldLangDir, $newLangDir, false);
			}
		}
	}
	
	$oldDir = resource_path('lang/');
	$newDir = resource_path('lang-depreciated/');
	if (File::exists($oldDir)) {
		File::moveDirectory($oldDir, $newDir, true);
	}
	
	File::deleteDirectory(resource_path('docs/'));
	File::delete(app_path('Http/Resources/SavedPostsResource.php'));
	
} catch (\Throwable $e) {
}
