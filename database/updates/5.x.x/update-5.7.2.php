<?php

use App\Exceptions\Custom\CustomException;
use Illuminate\Support\Facades\DB;

// ===| DATABASE |===
try {
	
	// Drop the function & Remove its DB Cache
	$functions = ['haversine', 'orthodromy'];
	foreach ($functions as $function) {
		// Drop function
		DB::unprepared('DROP FUNCTION IF EXISTS `' . $function . '`;');
		
		// Remove the function's cache
		$cacheId = 'checkIfMySQLFunctionExists.' . $function;
		if (cache()->has($cacheId)) {
			cache()->forget($cacheId);
		}
	}
	
} catch (\Throwable $e) {
	
	$message = $e->getMessage() . "\n" . 'in ' . str_replace(base_path(), '', __FILE__);
	throw new CustomException($message);
	
}
