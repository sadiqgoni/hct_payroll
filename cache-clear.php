<?php
/**
 * Web-based Laravel Cache Clear Script
 * 
 * Upload this to: public_html/cache-clear.php
 * Access via: https://kssbonline.org/cache-clear.php?key=YOUR_SECRET_KEY_HERE
 * 
 * IMPORTANT: Change the SECRET_KEY below!
 */

// Set your secret key (CHANGE THIS!)
define('SECRET_KEY', 'KanoNigeria' . md5('kssbonline-cache'));

// Security check
if (!isset($_GET['key']) || $_GET['key'] !== SECRET_KEY) {
    http_response_code(403);
    die('Access denied. Invalid key.');
}

// Set time limit
set_time_limit(60);

// Output buffer
header('Content-Type: text/plain; charset=utf-8');
echo "=== Laravel Cache Clear ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

// Change to application directory
$appPath = __DIR__;
chdir($appPath);

// Function to execute command and display output
function runCommand($command, $description) {
    echo "=== $description ===\n";
    
    $output = [];
    $returnVar = 0;
    
    exec($command . ' 2>&1', $output, $returnVar);
    
    echo implode("\n", $output) . "\n";
    echo "Status: " . ($returnVar === 0 ? "✓ Success" : "✗ Failed") . "\n\n";
    
    return $returnVar === 0;
}

// Get action
$action = $_GET['action'] ?? 'all';

switch ($action) {
    case 'all':
        echo "Clearing ALL caches...\n\n";
        runCommand('php artisan cache:clear', 'Clear Application Cache');
        runCommand('php artisan config:clear', 'Clear Config Cache');
        runCommand('php artisan route:clear', 'Clear Route Cache');
        runCommand('php artisan view:clear', 'Clear View Cache');
        runCommand('php artisan clear-compiled', 'Clear Compiled Classes');
        break;
        
    case 'cache':
        runCommand('php artisan cache:clear', 'Clear Application Cache');
        break;
        
    case 'config':
        runCommand('php artisan config:clear', 'Clear Config Cache');
        runCommand('php artisan config:cache', 'Rebuild Config Cache');
        break;
        
    case 'route':
        runCommand('php artisan route:clear', 'Clear Route Cache');
        runCommand('php artisan route:cache', 'Rebuild Route Cache');
        break;
        
    case 'view':
        runCommand('php artisan view:clear', 'Clear View Cache');
        runCommand('php artisan view:cache', 'Rebuild View Cache');
        break;
        
    case 'optimize':
        echo "Optimizing application...\n\n";
        runCommand('php artisan optimize:clear', 'Clear All Optimization Caches');
        runCommand('php artisan config:cache', 'Cache Configuration');
        runCommand('php artisan route:cache', 'Cache Routes');
        runCommand('php artisan view:cache', 'Cache Views');
        runCommand('php artisan optimize', 'Optimize Application');
        break;
        
    default:
        echo "Unknown action: $action\n\n";
        echo "Available actions:\n";
        echo "- all (default): Clear all caches\n";
        echo "- cache: Clear application cache only\n";
        echo "- config: Clear and rebuild config cache\n";
        echo "- route: Clear and rebuild route cache\n";
        echo "- view: Clear and rebuild view cache\n";
        echo "- optimize: Clear all and optimize\n";
        break;
}

echo "\n=== Usage Examples ===\n";
echo "Clear all: ?key=YOUR_KEY\n";
echo "Clear all: ?key=YOUR_KEY&action=all\n";
echo "Clear only config: ?key=YOUR_KEY&action=config\n";
echo "Full optimize: ?key=YOUR_KEY&action=optimize\n";

echo "\n=== DONE ===\n";
echo "Completed at: " . date('Y-m-d H:i:s') . "\n";
