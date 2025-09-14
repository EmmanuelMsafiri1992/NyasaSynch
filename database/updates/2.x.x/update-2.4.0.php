<?php

use App\Helpers\DotenvEditor;
use Illuminate\Support\Facades\File;

// ===| FILES |===
try {
	
	File::deleteDirectory(base_path('documentation/updates/'));
	File::deleteDirectory(storage_path('database/updates/'));
	
	// .ENV
	if (DotenvEditor::keyExists('DB_PREFIX')) {
		$dbTablesPrefix = DotenvEditor::getValue('DB_PREFIX');
		DotenvEditor::setKey('DB_TABLES_PREFIX', $dbTablesPrefix);
		DotenvEditor::deleteKey('DB_PREFIX');
	}
	DotenvEditor::deleteKey('SESSION_DOMAIN');
	DotenvEditor::save();
	
} catch (\Throwable $e) {
}
