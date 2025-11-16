<?php
// api/check_pusher_installed.php
header('Content-Type: text/plain');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== PUSHER INSTALLATION CHECK ===\n\n";

$checks = [
    'vendor/autoload.php' => 'Composer Autoload',
    'vendor/pusher/pusher-php-server/src/Pusher.php' => 'Pusher SDK Main File',
    'vendor/pusher/pusher-php-server/composer.json' => 'Pusher Composer Config',
    'vendor/composer/installed.json' => 'Composer Installed Packages',
];

foreach ($checks as $file => $description) {
    $full_path = '../' . $file;
    echo "$description: ";
    if (file_exists($full_path)) {
        echo "✅ EXISTS\n";
        
        // Show some details for key files
        if ($file === 'vendor/composer/installed.json') {
            $installed = json_decode(file_get_contents($full_path), true);
            if (isset($installed['packages'])) {
                foreach ($installed['packages'] as $package) {
                    if (strpos($package['name'], 'pusher') !== false) {
                        echo "   📦 Found: {$package['name']} v{$package['version']}\n";
                    }
                }
            }
        }
        
        if ($file === 'vendor/pusher/pusher-php-server/composer.json') {
            $composer = json_decode(file_get_contents($full_path), true);
            echo "   📦 Package: {$composer['name']} v{$composer['version']}\n";
        }
        
    } else {
        echo "❌ MISSING\n";
    }
}

echo "\n=== CLASS EXISTENCE CHECK ===\n";
echo "Pusher\\Pusher class: ";
if (class_exists('Pusher\\Pusher')) {
    echo "✅ LOADED\n";
} else {
    echo "❌ NOT LOADED\n";
    
    // Try to load it manually
    echo "Attempting to load manually... ";
    if (file_exists('../vendor/pusher/pusher-php-server/src/Pusher.php')) {
        require_once '../vendor/pusher/pusher-php-server/src/Pusher.php';
        if (class_exists('Pusher\\Pusher')) {
            echo "✅ SUCCESS - Class now loaded!\n";
        } else {
            echo "❌ FAILED - File exists but class not found\n";
        }
    } else {
        echo "❌ FAILED - Pusher.php not found\n";
    }
}

echo "\n=== COMPOSER.JSON CHECK ===\n";
if (file_exists('../composer.json')) {
    $composer = json_decode(file_get_contents('../composer.json'), true);
    echo "Project composer.json exists\n";
    
    if (isset($composer['require'])) {
        foreach ($composer['require'] as $package => $version) {
            if (strpos($package, 'pusher') !== false) {
                echo "📦 Required: $package ($version)\n";
            }
        }
    }
} else {
    echo "composer.json not found\n";
}

echo "\n=== QUICK FUNCTIONAL TEST ===\n";
try {
    if (class_exists('Pusher\\Pusher')) {
        $pusher = new Pusher\Pusher(
            'test-key',
            'test-secret', 
            'test-app',
            ['cluster' => 'us2', 'useTLS' => true]
        );
        echo "✅ Pusher class can be instantiated\n";
    } else {
        echo "❌ Cannot instantiate Pusher (class not loaded)\n";
    }
} catch (Exception $e) {
    echo "❌ Instantiation failed: " . $e->getMessage() . "\n";
}
?>