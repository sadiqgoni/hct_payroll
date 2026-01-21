<?php
// COMPOSER INSTALLER FOR CPANEL - DELETE AFTER USE!
set_time_limit(600); // 10 minutes timeout

echo "<!DOCTYPE html><html><head><title>Installing Dependencies</title></head><body>";
echo "<h1>Installing Composer Dependencies</h1>";
echo "<pre style='background:#f5f5f5; padding:20px; border:1px solid #ddd;'>";

// Set environment variables
putenv('HOME=' . __DIR__);
putenv('COMPOSER_HOME=' . __DIR__ . '/.composer');

echo "Setting up environment...\n";
echo "Working directory: " . __DIR__ . "\n\n";

// Download Composer
if (!file_exists('composer.phar')) {
    echo "Downloading Composer installer...\n";
    
    $installer = file_get_contents('https://getcomposer.org/installer');
    if ($installer === false) {
        die("❌ Failed to download Composer installer!\n");
    }
    
    file_put_contents('composer-setup.php', $installer);
    
    echo "Running Composer installer...\n";
    $output = [];
    $return = 0;
    exec('php composer-setup.php 2>&1', $output, $return);
    echo implode("\n", $output) . "\n";
    
    unlink('composer-setup.php');
    
    if (!file_exists('composer.phar')) {
        die("\n❌ Composer installation failed!\n");
    }
    
    echo "\n✅ Composer downloaded successfully!\n\n";
}

// Install dependencies
echo "Installing Laravel dependencies (this may take 2-3 minutes)...\n";
echo str_repeat("=", 60) . "\n\n";

$cmd = 'php composer.phar install --no-dev --optimize-autoloader --no-interaction 2>&1';
$output2 = [];
$return2 = 0;

exec($cmd, $output2, $return2);

foreach ($output2 as $line) {
    echo $line . "\n";
}

echo "\n" . str_repeat("=", 60) . "\n";

if (file_exists('vendor/autoload.php')) {
    echo "\n✅ SUCCESS! Dependencies installed!\n";
    echo "\nNext steps:\n";
    echo "1. Delete this file (install-composer.php)\n";
    echo "2. Delete composer.phar\n";
    echo "3. Visit your site to test\n";
} else {
    echo "\n❌ Installation may have failed!\n";
    echo "\nTry manually:\n";
    echo "1. SSH into server\n";
    echo "2. Run: cd " . __DIR__ . "\n";
    echo "3. Run: composer install --no-dev\n";
}

echo "</pre>";
echo "<h2 style='color:red;'>⚠️ DELETE THIS FILE AND composer.phar NOW!</h2>";
echo "</body></html>";
?>
