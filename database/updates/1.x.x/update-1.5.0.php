<?php

use App\Exceptions\Custom\CustomException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ===| DATABASE |===
try {
	
	// cities
	if (Schema::hasColumn('cities', 'id')) {
		Schema::table('cities', function (Blueprint $table) {
			$table->bigInteger('id')->unsigned()->autoIncrement()->change();
		});
	}
	
} catch (\Throwable $e) {
	
	$message = $e->getMessage() . "\n" . 'in ' . str_replace(base_path(), '', __FILE__);
	throw new CustomException($message);
	
}
