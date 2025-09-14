<?php

use Illuminate\Support\Facades\File;

// ===| FILES |===
try {
	
	File::delete(app_path('Http/Controllers/Web/Post/DetailsController.php'));
	File::delete(resource_path('views/post/details.blade.php'));
	
} catch (\Throwable $e) {
}
