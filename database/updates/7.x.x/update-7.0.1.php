<?php

use App\Exceptions\Custom\CustomException;
use Illuminate\Support\Facades\File;

// ===| FILES |===
try {
	
	$filePath = app_path('Macros/EloquentBuilderMacros.php');
	if (File::exists($filePath)) {
		File::delete(app_path('Macros/EloquentBuilderMacros.php'));
	}
	
	$filePath = app_path('Macros/QueryBuilderMacros.php');
	if (File::exists($filePath)) {
		File::delete(app_path('Macros/QueryBuilderMacros.php'));
	}
	
} catch (\Throwable $e) {
	
	$message = $e->getMessage() . "\n" . 'in ' . str_replace(base_path(), '', __FILE__);
	throw new CustomException($message);
	
}
