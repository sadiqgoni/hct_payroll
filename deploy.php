<?php
/**
 * DEPLOYMENT SCRIPT FOR CPANEL (NO TERMINAL ACCESS)
 * 
 * ⚠️ IMPORTANT SECURITY:
 * 1. Change the SECRET_KEY below to a random string
 * 2. DELETE this file after deployment is complete!
 * 
 * Usage:
 * http://yoursite.com/deploy.php?action=migrate&key=YOUR_SECRET_KEY
 */

// ⚠️ CHANGE THIS TO A RANDOM STRING!
define('SECRET_KEY', 'hct_deploy_2026_change_this_key_12345');

// Security check
if (!isset($_GET['key']) || $_GET['key'] !== SECRET_KEY) {
    die('❌ Access Denied - Invalid or missing key');
}

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$action = $_GET['action'] ?? 'help';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deployment Script</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 10px;
        }
        pre {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            overflow-x: auto;
            font-size: 13px;
        }
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
        }
        .danger {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin: 20px 0;
            font-weight: bold;
        }
        .success {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 20px 0;
        }
        .actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin: 20px 0;
        }
        .action-btn {
            background: #4CAF50;
            color: white;
            padding: 12px;
            text-align: center;
            text-decoration: none;
            border-radius: 4px;
            display: block;
        }
        .action-btn:hover {
            background: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 HCT Payroll Deployment Script</h1>
        
        <?php
        
        echo "<pre>";
        
        switch($action) {
            case 'migrate':
                echo "🔄 Running database migrations...\n";
                echo str_repeat("=", 60) . "\n\n";
                
                try {
                    $kernel->call('migrate', ['--force' => true]);
                    echo "\n" . str_repeat("=", 60) . "\n";
                    echo "✅ MIGRATIONS COMPLETED SUCCESSFULLY!\n";
                    echo "</pre>";
                    echo "<div class='success'>Database migrations have been applied. All new tables and columns are ready!</div>";
                } catch (Exception $e) {
                    echo "\n" . str_repeat("=", 60) . "\n";
                    echo "❌ ERROR: " . $e->getMessage() . "\n";
                    echo "</pre>";
                    echo "<div class='danger'>Migration failed! Check error above. Your database was not modified.</div>";
                }
                break;
                
            case 'seed-tax':
                echo "🌱 Seeding tax brackets table...\n";
                echo str_repeat("=", 60) . "\n\n";
                
                try {
                    $kernel->call('db:seed', [
                        '--class' => 'TaxBracketSeeder',
                        '--force' => true
                    ]);
                    echo "\n" . str_repeat("=", 60) . "\n";
                    echo "✅ TAX BRACKETS SEEDED SUCCESSFULLY!\n";
                    echo "</pre>";
                    echo "<div class='success'>Tax brackets have been populated in the database!</div>";
                } catch (Exception $e) {
                    echo "\n" . str_repeat("=", 60) . "\n";
                    echo "❌ ERROR: " . $e->getMessage() . "\n";
                    echo "</pre>";
                    echo "<div class='danger'>Seeding failed! Check error above.</div>";
                }
                break;
                
            case 'cache-clear':
                echo "🧹 Clearing all caches...\n";
                echo str_repeat("=", 60) . "\n\n";
                
                try {
                    echo "Clearing configuration cache...\n";
                    $kernel->call('config:clear');
                    
                    echo "Clearing application cache...\n";
                    $kernel->call('cache:clear');
                    
                    echo "Clearing view cache...\n";
                    $kernel->call('view:clear');
                    
                    echo "Clearing route cache...\n";
                    $kernel->call('route:clear');
                    
                    echo "\n" . str_repeat("=", 60) . "\n";
                    echo "✅ ALL CACHES CLEARED!\n";
                    echo "</pre>";
                    echo "<div class='success'>All caches have been cleared successfully!</div>";
                } catch (Exception $e) {
                    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
                    echo "</pre>";
                    echo "<div class='danger'>Cache clearing failed!</div>";
                }
                break;
                
            case 'optimize':
                echo "⚡ Optimizing application...\n";
                echo str_repeat("=", 60) . "\n\n";
                
                try {
                    echo "Caching configuration...\n";
                    $kernel->call('config:cache');
                    
                    echo "Caching routes...\n";
                    $kernel->call('route:cache');
                    
                    echo "Caching views...\n";
                    $kernel->call('view:cache');
                    
                    echo "\n" . str_repeat("=", 60) . "\n";
                    echo "✅ APPLICATION OPTIMIZED!\n";
                    echo "</pre>";
                    echo "<div class='success'>Application has been optimized for production!</div>";
                } catch (Exception $e) {
                    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
                    echo "</pre>";
                    echo "<div class='danger'>Optimization failed!</div>";
                }
                break;
                
            case 'status':
                echo "📊 Checking migration status...\n";
                echo str_repeat("=", 60) . "\n\n";
                
                try {
                    $kernel->call('migrate:status');
                    echo "\n" . str_repeat("=", 60) . "\n";
                    echo "</pre>";
                } catch (Exception $e) {
                    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
                    echo "</pre>";
                }
                break;
                
            default:
                echo "</pre>";
                
                echo "<div class='warning'>";
                echo "<h3>📋 Deployment Steps:</h3>";
                echo "<ol>";
                echo "<li>First, clear caches</li>";
                echo "<li>Check migration status</li>";
                echo "<li>Run migrations</li>";
                echo "<li>Seed tax brackets (if needed)</li>";
                echo "<li>Optimize application</li>";
                echo "<li><strong>DELETE this deploy.php file!</strong></li>";
                echo "</ol>";
                echo "</div>";
                
                echo "<h2>Available Actions:</h2>";
                echo "<div class='actions'>";
                
                $key = SECRET_KEY;
                $actions = [
                    'cache-clear' => '🧹 Clear Caches',
                    'status' => '📊 Migration Status',
                    'migrate' => '🔄 Run Migrations',
                    'seed-tax' => '🌱 Seed Tax Brackets',
                    'optimize' => '⚡ Optimize App'
                ];
                
                foreach ($actions as $act => $label) {
                    echo "<a href='?action={$act}&key={$key}' class='action-btn'>{$label}</a>";
                }
                
                echo "</div>";
        }
        
        ?>
        
        <div class='danger' style='margin-top: 30px;'>
            <h3>⚠️ SECURITY WARNING:</h3>
            <p>DELETE this deploy.php file immediately after completing deployment!</p>
            <p>Leaving it accessible is a security risk.</p>
        </div>
        
        <div style='margin-top: 20px; padding: 15px; background: #e9ecef; border-radius: 4px;'>
            <small>
                <strong>Current Action:</strong> <?php echo htmlspecialchars($action); ?><br>
                <strong>Time:</strong> <?php echo date('Y-m-d H:i:s'); ?><br>
                <strong>PHP Version:</strong> <?php echo phpversion(); ?>
            </small>
        </div>
    </div>
</body>
</html>
