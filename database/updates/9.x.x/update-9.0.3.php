<?php

use Illuminate\Support\Facades\File;

try {
	
	$oldPath = storage_path('app/public/resumes/');
	$newPath = storage_path('app/private/resumes/');
	if (File::exists($oldPath)) {
		File::moveDirectory($oldPath, $newPath, true);
	}
	
} catch (\Throwable $e) {
}
