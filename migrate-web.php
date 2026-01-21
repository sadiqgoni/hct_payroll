<?php
/**
 * Web-based Laravel Migration Script
 * 
 * Upload this to: public_html/migrate-web.php
 * Access via: https://kssbonline.org/migrate-web.php?key=YOUR_SECRET_KEY_HERE
 * 
 * IMPORTANT: Change the SECRET_KEY below!
 */

// Set your secret key (CHANGE THIS!)
define('SECRET_KEY', 'KanoNigeria' . md5('kssbonline-migrate'));

// Security check
if (!isset($_GET['key']) || $_GET['key'] !== SECRET_KEY) {
    http_response_code(403);
    die('Access denied. Invalid key.');
}

// Set time limit
set_time_limit(120);

// Output buffer
header('Content-Type: text/plain; charset=utf-8');
echo "=== Laravel Migration Runner ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

// Change to application directory
$appPath = __DIR__;
chdir($appPath);

// Function to execute command and display output
function runCommand($command, $description) {
    echo "=== $description ===\n";
    echo "Command: $command\n\n";
    
    $output = [];
    $returnVar = 0;
    
    exec($command . ' 2>&1', $output, $returnVar);
    
    echo implode("\n", $output) . "\n";
    echo "\nStatus: " . ($returnVar === 0 ? "✓ Success" : "✗ Failed (code: $returnVar)") . "\n\n";
    
    return $returnVar === 0;
}

// Check what action to perform
$action = $_GET['action'] ?? 'migrate';

switch ($action) {
    case 'migrate':
        runCommand('php artisan migrate --force', 'Run Migrations');
        break;
        
    case 'rollback':
        runCommand('php artisan migrate:rollback --force', 'Rollback Last Migration');
        break;
        
    case 'fresh':
        echo "⚠️ WARNING: This will drop all tables!\n";
        if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
            runCommand('php artisan migrate:fresh --force', 'Fresh Migration');
        } else {
            echo "Add &confirm=yes to the URL to confirm this action.\n";
        }
        break;
        
    case 'seed':
        $seeder = $_GET['seeder'] ?? '';
        if ($seeder) {
            runCommand("php artisan db:seed --class={$seeder} --force", "Run Seeder: {$seeder}");
        } else {
            runCommand('php artisan db:seed --force', 'Run All Seeders');
        }
        break;
        
    case 'status':
        runCommand('php artisan migrate:status', 'Migration Status');
        break;
        
    default:
        echo "Unknown action: $action\n";
        echo "\nAvailable actions:\n";
        echo "- migrate (default): Run pending migrations\n";
        echo "- rollback: Rollback last migration\n";
        echo "- status: Show migration status\n";
        echo "- seed: Run seeders\n";
        echo "- fresh: Drop all tables and re-migrate (requires &confirm=yes)\n";
        break;
}

echo "\n=== Usage Examples ===\n";
echo "Run migrations: ?key=YOUR_KEY&action=migrate\n";
echo "Rollback: ?key=YOUR_KEY&action=rollback\n";
echo "Status: ?key=YOUR_KEY&action=status\n";
echo "Run seeder: ?key=YOUR_KEY&action=seed&seeder=TaxBracketSeeder\n";

echo "\n=== DONE ===\n";
echo "Completed at: " . date('Y-m-d H:i:s') . "\n";
