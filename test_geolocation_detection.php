<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Helpers\GeoIP;
use App\Models\Country;

try {
    echo "=== Testing Geolocation Detection ===\n";

    // Check current GeoIP configuration
    echo "GeoIP Driver: " . config('geoip.default', 'Not set') . "\n";
    echo "Random IP enabled: " . (config('geoip.randomIp', false) ? 'Yes' : 'No') . "\n";

    // Test current user's IP detection
    echo "\n--- Testing Current IP Detection ---\n";
    $geoip = new GeoIP();
    $currentIp = $geoip->getIp();
    echo "Detected IP: $currentIp\n";

    try {
        $locationData = $geoip->getData();
        echo "Location data: " . json_encode($locationData) . "\n";

        if (isset($locationData['countryCode'])) {
            $country = Country::where('code', $locationData['countryCode'])->first();
            if ($country) {
                echo "Country found in database: {$country->name} ({$country->code})\n";
                echo "Country is active: " . ($country->active ? 'Yes' : 'No') . "\n";
            } else {
                echo "Country {$locationData['countryCode']} not found in database\n";
            }
        }
    } catch (Exception $e) {
        echo "Error getting location data: " . $e->getMessage() . "\n";
    }

    // Test the driver capabilities
    echo "\n--- Testing GeoIP Driver ---\n";
    try {
        $driver = $geoip->getDriver();
        echo "Using driver: " . get_class($driver) . "\n";
    } catch (Exception $e) {
        echo "Driver error: " . $e->getMessage() . "\n";
    }

    // Check for country detection middleware
    echo "\n=== Checking Country Detection Middleware ===\n";
    $middlewareFiles = [
        app_path('Http/Middleware/GeoLocationMiddleware.php'),
        app_path('Http/Middleware/DetectCountry.php'),
        app_path('Http/Middleware/LocationDetector.php'),
        app_path('Http/Middleware/CountryDetection.php')
    ];

    $middlewareFound = false;
    foreach ($middlewareFiles as $file) {
        if (file_exists($file)) {
            echo "Found middleware: $file\n";
            $middlewareFound = true;
        }
    }

    if (!$middlewareFound) {
        echo "No country detection middleware found in common locations\n";
    }

    // Check session/cookie for country detection
    echo "\n=== Checking Session/Cookie Country Storage ===\n";
    if (!session_id()) {
        session_start();
    }

    echo "Current session country: " . ($_SESSION['country_code'] ?? 'Not set') . "\n";
    echo "Cookie country: " . ($_COOKIE['country_code'] ?? 'Not set') . "\n";

    // Check MaxMind database files
    echo "\n=== Checking MaxMind Database Files ===\n";
    $mmdbFiles = [
        storage_path('database/maxmind/GeoLite2-Country.mmdb'),
        storage_path('database/maxmind/GeoLite2-City.mmdb'),
        storage_path('app/geoip/GeoLite2-Country.mmdb'),
        storage_path('app/geoip/GeoLite2-City.mmdb'),
        base_path('database/geoip/GeoLite2-Country.mmdb'),
        base_path('database/geoip/GeoLite2-City.mmdb')
    ];

    $mmdbFound = false;
    foreach ($mmdbFiles as $mmdbFile) {
        if (file_exists($mmdbFile)) {
            echo "Found MMDB file: $mmdbFile\n";
            echo "File size: " . number_format(filesize($mmdbFile)) . " bytes\n";
            $mmdbFound = true;
        }
    }

    if (!$mmdbFound) {
        echo "No MMDB files found in common locations\n";
    }

    // Check configuration
    echo "\n=== Configuration Check ===\n";
    $configFiles = [
        'app.country_code_autodetection',
        'app.default_country_code'
    ];

    foreach ($configFiles as $configKey) {
        try {
            $value = config($configKey);
            if ($value !== null) {
                echo "$configKey: " . (is_array($value) ? json_encode($value) : $value) . "\n";
            } else {
                echo "$configKey: Not set\n";
            }
        } catch (Exception $e) {
            echo "$configKey: Error - " . $e->getMessage() . "\n";
        }
    }

    // Check localization settings from database
    echo "\n=== Database Settings Check ===\n";
    $localizationSettings = DB::table('settings')->where('key', 'localization')->first();
    if ($localizationSettings) {
        $locData = json_decode($localizationSettings->value, true);
        echo "Localization settings from database:\n";
        echo "- Default country: " . ($locData['default_country_code'] ?? 'Not set') . "\n";
        echo "- Current country: " . ($locData['country_code'] ?? 'Not set') . "\n";

        // Show other relevant settings
        foreach ($locData as $key => $value) {
            if (strpos($key, 'country') !== false || strpos($key, 'location') !== false) {
                echo "- {$key}: {$value}\n";
            }
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}