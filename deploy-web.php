<?php
/**
 * Web-based Laravel Deployment Script
 * 
 * Upload this to: public_html/deploy-web.php
 * Access via: https://kssbonline.org/deploy-web.php?key=YOUR_SECRET_KEY_HERE
 * 
 * IMPORTANT: Change the SECRET_KEY below!
 */

// Set your secret key (CHANGE THIS!)
define('SECRET_KEY', 'KanoNigeria' . md5('kssbonline'));

// Security check
if (!isset($_GET['key']) || $_GET['key'] !== SECRET_KEY) {
    http_response_code(403);
    die('Access denied. Invalid key.');
}

// Set time limit
set_time_limit(300);

// Output buffer
header('Content-Type: text/plain; charset=utf-8');
echo "=== Laravel Deployment Script ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

// Change to application directory
$appPath = __DIR__;
chdir($appPath);

echo "Application path: $appPath\n\n";

// Function to execute command and display output
function runCommand($command, $description) {
    echo "=== $description ===\n";
    echo "Command: $command\n";
    
    $output = [];
    $returnVar = 0;
    
    exec($command . ' 2>&1', $output, $returnVar);
    
    echo implode("\n", $output) . "\n";
    echo "Status: " . ($returnVar === 0 ? "✓ Success" : "✗ Failed (code: $returnVar)") . "\n\n";
    
    return $returnVar === 0;
}

// Deployment steps
$steps = [
    // Enable maintenance mode
    ['php artisan down --retry=60', 'Enable Maintenance Mode'],
    
    // Clear caches
    ['php artisan cache:clear', 'Clear Application Cache'],
    ['php artisan config:clear', 'Clear Config Cache'],
    ['php artisan route:clear', 'Clear Route Cache'],
    ['php artisan view:clear', 'Clear View Cache'],
    
    // Install dependencies (if needed)
    // Uncomment if you want to run composer install
    // ['composer install --optimize-autoloader --no-dev', 'Install Dependencies'],
    
    // Run migrations
    ['php artisan migrate --force', 'Run Database Migrations'],
    
    // Cache optimization
    ['php artisan config:cache', 'Cache Configuration'],
    // NOTE: route:cache will fail if there are duplicate route names (e.g., 'login').
    // Keep this disabled unless route names are unique.
    // ['php artisan route:cache', 'Cache Routes'],
    ['php artisan view:cache', 'Cache Views'],
    
    // Optimize
    ['php artisan optimize', 'Optimize Application'],
    
    // Disable maintenance mode
    ['php artisan up', 'Disable Maintenance Mode'],
];

$successCount = 0;
$failCount = 0;

foreach ($steps as $step) {
    if (runCommand($step[0], $step[1])) {
        $successCount++;
    } else {
        $failCount++;
    }
}

// Summary
echo "=== Deployment Summary ===\n";
echo "Completed at: " . date('Y-m-d H:i:s') . "\n";
echo "Successful steps: $successCount\n";
echo "Failed steps: $failCount\n";
echo "\n";

if ($failCount === 0) {
    echo "✅ DEPLOYMENT SUCCESSFUL!\n";
} else {
    echo "⚠️ DEPLOYMENT COMPLETED WITH ERRORS\n";
    echo "Check the output above for details.\n";
}

// Fix permissions
echo "\n=== Fixing Permissions ===\n";
$dirs = ['storage', 'bootstrap/cache'];
foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        chmod($dir, 0775);
        echo "✓ Set permissions for $dir\n";
    }
}

echo "\n=== DONE ===\n";
