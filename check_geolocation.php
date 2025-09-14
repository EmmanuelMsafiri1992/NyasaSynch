<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

try {
    echo "=== Checking Geolocation and Multi-Country Setup ===" . PHP_EOL;

    // Check countries in the database
    $countryCount = \App\Models\Country::count();
    echo "Countries in database: {$countryCount}" . PHP_EOL;

    if ($countryCount > 0) {
        // Show first few countries
        $countries = \App\Models\Country::limit(10)->get(['code', 'name', 'active']);
        echo "\nSample countries:" . PHP_EOL;
        foreach ($countries as $country) {
            $status = $country->active ? 'Active' : 'Inactive';
            echo "- {$country->code}: {$country->name} ({$status})" . PHP_EOL;
        }

        // Check for Malawi specifically
        $malawi = \App\Models\Country::where('code', 'MW')->first();
        if ($malawi) {
            echo "\n✓ Malawi found: {$malawi->name} - " . ($malawi->active ? 'Active' : 'Inactive') . PHP_EOL;
        } else {
            echo "\n✗ Malawi (MW) not found in countries" . PHP_EOL;
        }
    }

    // Check current configuration settings
    echo "\n=== Configuration Check ===" . PHP_EOL;

    // Check localization settings
    $localizationSettings = DB::table('settings')->where('key', 'localization')->first();
    if ($localizationSettings) {
        $locData = json_decode($localizationSettings->value, true);
        echo "Localization settings:" . PHP_EOL;
        echo "- Default country: " . ($locData['default_country_code'] ?? 'Not set') . PHP_EOL;
        echo "- Current country: " . ($locData['country_code'] ?? 'Not set') . PHP_EOL;
    }

    // Check if multi-country is enabled
    $appSettings = DB::table('settings')->where('key', 'app')->first();
    if ($appSettings) {
        $appData = json_decode($appSettings->value, true);
        echo "\nApp settings:" . PHP_EOL;
        foreach ($appData as $key => $value) {
            if (strpos($key, 'country') !== false || strpos($key, 'location') !== false) {
                echo "- {$key}: {$value}" . PHP_EOL;
            }
        }
    }

    // Check posts by country
    echo "\n=== Posts by Country ===" . PHP_EOL;
    $postsByCountry = DB::table('posts')
        ->select('country_code', DB::raw('COUNT(*) as count'))
        ->groupBy('country_code')
        ->orderBy('count', 'desc')
        ->limit(10)
        ->get();

    foreach ($postsByCountry as $stat) {
        $countryName = \App\Models\Country::where('code', $stat->country_code)->value('name') ?? 'Unknown';
        echo "- {$stat->country_code} ({$countryName}): {$stat->count} posts" . PHP_EOL;
    }

    // Check for geolocation packages/plugins
    echo "\n=== Checking Geolocation Support ===" . PHP_EOL;

    // Check if GeoIP is configured
    if (function_exists('geoip_record_by_name')) {
        echo "✓ PHP GeoIP extension available" . PHP_EOL;
    } else {
        echo "✗ PHP GeoIP extension not available" . PHP_EOL;
    }

    // Check for GeoIP2 package
    if (class_exists('GeoIp2\Database\Reader')) {
        echo "✓ GeoIP2 package available" . PHP_EOL;
    } else {
        echo "✗ GeoIP2 package not available" . PHP_EOL;
    }

    // Check middleware or routes that handle country switching
    echo "\n=== Route Analysis ===" . PHP_EOL;

    // Check if there are country-specific routes or middleware
    $routesOutput = shell_exec('cd "C:\laragon\www\updated\v1414" && php artisan route:list | grep -i country || echo "No country routes found"');
    echo "Country-related routes:" . PHP_EOL;
    echo $routesOutput;

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
    echo "Stack trace: " . $e->getTraceAsString() . PHP_EOL;
}