# 🚀 DEPLOYMENT WITHOUT TERMINAL/SSH ACCESS

## ✅ How to Deploy When You DON'T Have Terminal Access

Many shared hosting providers (cPanel) don't give you SSH/terminal access. Here's how to deploy anyway:

---

## 📋 PREPARATION ON LOCALHOST

### Step 1: Create a Deployment Script

Create a special PHP file that will run migrations and commands for you:

**File:** `deploy.php` (in your project root)

```php
<?php
// DEPLOYMENT SCRIPT - DELETE THIS FILE AFTER DEPLOYMENT!
// Access: http://yoursite.com/deploy.php?action=migrate&key=YOUR_SECRET_KEY

define('SECRET_KEY', 'change_this_to_random_string_12345'); // Change this!

if (!isset($_GET['key']) || $_GET['key'] !== SECRET_KEY) {
    die('Access Denied');
}

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$action = $_GET['action'] ?? 'help';

echo "<h1>Deployment Script</h1>";
echo "<pre>";

switch($action) {
    case 'migrate':
        echo "Running migrations...\n";
        $kernel->call('migrate', ['--force' => true]);
        echo "\n✅ Migrations completed!\n";
        break;
        
    case 'seed-tax':
        echo "Seeding tax brackets...\n";
        $kernel->call('db:seed', ['--class' => 'TaxBracketSeeder', '--force' => true]);
        echo "\n✅ Tax brackets seeded!\n";
        break;
        
    case 'cache-clear':
        echo "Clearing caches...\n";
        $kernel->call('config:clear');
        $kernel->call('cache:clear');
        $kernel->call('view:clear');
        $kernel->call('route:clear');
        echo "\n✅ All caches cleared!\n";
        break;
        
    case 'optimize':
        echo "Optimizing application...\n";
        $kernel->call('config:cache');
        $kernel->call('route:cache');
        $kernel->call('view:cache');
        echo "\n✅ Application optimized!\n";
        break;
        
    case 'status':
        echo "Migration status:\n";
        $kernel->call('migrate:status');
        break;
        
    default:
        echo "Available actions:\n";
        echo "- migrate: Run database migrations\n";
        echo "- seed-tax: Seed tax brackets table\n";
        echo "- cache-clear: Clear all caches\n";
        echo "- optimize: Optimize application\n";
        echo "- status: Check migration status\n";
        echo "\nUsage: deploy.php?action=ACTION&key=YOUR_SECRET_KEY\n";
}

echo "</pre>";
echo "<p style='color:red;'><strong>⚠️ DELETE THIS FILE AFTER DEPLOYMENT!</strong></p>";
?>
```

---

## 🎯 DEPLOYMENT STEPS (NO TERMINAL NEEDED)

### Step 1: Backup Production Database

1. Login to **cPanel**
2. Go to **phpMyAdmin**
3. Select your database
4. Click **Export** tab
5. Choose **Quick** export
6. Click **Go**
7. Save as: `backup_20jan2026.sql`

✅ **This is your safety net!**

---

### Step 2: Prepare Files on Localhost

```bash
# On your localhost terminal
cd /Users/macbookpro/Downloads/hct

# Create the deploy.php file (copy the code above)
# Change the SECRET_KEY to something random like: "hct_deploy_2026_xyz789"

# Compress project (excluding large folders)
zip -r hct_deploy_20jan2026.zip . \
  -x "vendor/*" \
  -x "node_modules/*" \
  -x "storage/logs/*" \
  -x "storage/framework/cache/*" \
  -x "storage/framework/sessions/*" \
  -x "storage/framework/views/*" \
  -x ".git/*" \
  -x ".env"
```

---

### Step 3: Upload to Production

1. Login to **cPanel**
2. Go to **File Manager**
3. Navigate to your web root (usually `public_html` or `htdocs`)
4. **Rename** existing folder: `hct` → `hct_backup_20jan2026`
5. Click **Upload**
6. Upload `hct_deploy_20jan2026.zip`
7. Right-click the zip file → **Extract**
8. Move extracted files to `hct` folder

---

### Step 4: Copy Production .env File

1. In **File Manager**, go to `hct_backup_20jan2026` folder
2. Right-click `.env` file → **Copy**
3. Navigate to new `hct` folder
4. **Paste** the `.env` file
5. Click to overwrite

✅ **This ensures production database credentials are used!**

---

### Step 5: Install Composer Dependencies

**Option A: Using cPanel PHP Selector (if available)**

1. Go to **Select PHP Version** in cPanel
2. Click **Extensions**
3. Enable: `zip`, `mbstring`, `openssl`, `pdo_mysql`
4. Go to **File Manager** → `hct` folder
5. Right-click on `composer.json` → **Composer Install**

**Option B: Using Auto-Install Script**

Create `install.php` in your `hct` folder:

```php
<?php
// COMPOSER AUTO-INSTALLER - DELETE AFTER USE!
// Access: http://yoursite.com/install.php

echo "<h1>Installing Composer Dependencies</h1>";
echo "<pre>";

if (!file_exists('composer.phar')) {
    echo "Downloading Composer...\n";
    copy('https://getcomposer.org/installer', 'composer-setup.php');
    system('php composer-setup.php');
    unlink('composer-setup.php');
}

echo "\nInstalling dependencies...\n";
system('php composer.phar install --no-dev --optimize-autoloader 2>&1');

echo "\n✅ Dependencies installed!\n";
echo "</pre>";
echo "<p style='color:red;'><strong>⚠️ DELETE THIS FILE NOW!</strong></p>";
?>
```

Then visit: `http://yourproductionsite.com/install.php`

---

### Step 6: Run Migrations (Using deploy.php)

Visit these URLs in your browser:

**1. Clear caches:**
```
http://yourproductionsite.com/deploy.php?action=cache-clear&key=YOUR_SECRET_KEY
```

**2. Check migration status:**
```
http://yourproductionsite.com/deploy.php?action=status&key=YOUR_SECRET_KEY
```

**3. Run migrations:**
```
http://yourproductionsite.com/deploy.php?action=migrate&key=YOUR_SECRET_KEY
```

**4. Seed tax brackets:**
```
http://yourproductionsite.com/deploy.php?action=seed-tax&key=YOUR_SECRET_KEY
```

**5. Optimize application:**
```
http://yourproductionsite.com/deploy.php?action=optimize&key=YOUR_SECRET_KEY
```

---

### Step 7: Delete Deployment Files

**⚠️ VERY IMPORTANT FOR SECURITY!**

Go to **File Manager** and delete:
- `deploy.php`
- `install.php` (if you created it)
- `composer.phar` (if downloaded)

---

### Step 8: Test Production

Visit your site and test:
- ✅ Login works
- ✅ Employee data intact
- ✅ Tax brackets page works
- ✅ Group salary update shows new option
- ✅ Deduction countdown updates

---

## 🔧 ALTERNATIVE: Manual Migration via phpMyAdmin

If the deploy.php script doesn't work, you can run migrations manually:

### Step 1: Check Which Migrations to Run

On localhost:
```bash
ls -la database/migrations/
```

Find new migration files (like `*_create_tax_brackets_table.php`)

### Step 2: Create the Table Manually

Open the migration file, find the SQL structure, then:

1. Go to **phpMyAdmin** on production
2. Click **SQL** tab
3. Paste this SQL (example for tax_brackets):

```sql
CREATE TABLE IF NOT EXISTS `tax_brackets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `version_name` varchar(255) NOT NULL,
  `effective_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `tax_brackets` json NOT NULL,
  `reliefs` json DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tax_brackets_is_active_index` (`is_active`),
  KEY `tax_brackets_effective_date_index` (`effective_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

4. Click **Go**

### Step 3: Mark Migration as Run

Add to migrations table:
```sql
INSERT INTO `migrations` (`migration`, `batch`) 
VALUES ('2026_01_20_000000_create_tax_brackets_table', 1);
```

### Step 4: Seed Tax Brackets

You can insert data manually:
```sql
INSERT INTO `tax_brackets` 
(`version_name`, `effective_date`, `is_active`, `tax_brackets`, `reliefs`, `created_at`, `updated_at`) 
VALUES 
('PAYE 2026 - Standard Method', '2026-01-01', 1, 
'[{"min":0,"max":800000,"rate":0},{"min":800000,"max":3000000,"rate":15},{"min":3000000,"max":12000000,"rate":18},{"min":12000000,"max":50000000,"rate":23},{"min":50000000,"max":null,"rate":25}]',
'{"calculation_method":"standard","consolidated_relief":{"fixed":200000,"percentage_of_gross":20},"pension":{"percentage":8.0,"base":"basic","annual":true},"nhf":{"percentage":2.5,"base":"basic","annual":true},"nhis":{"percentage":0.05,"base":"basic","annual":true}}',
NOW(), NOW());
```

---

## 📱 ALTERNATIVE DEPLOYMENT METHODS

### Method 1: FTP + Browser-Based Migration

1. Upload files via **FTP** (FileZilla)
2. Use `deploy.php` script (via browser)
3. Delete `deploy.php` after

### Method 2: Git Deployment (if cPanel supports it)

Some cPanel hosts have **Git Version Control**:

1. cPanel → **Git Version Control**
2. Create repository
3. Pull from GitHub/GitLab
4. Use `deploy.php` for migrations

### Method 3: Request SSH Access

Contact your hosting provider:
- Ask for **SSH access** or **Terminal** in cPanel
- Many will enable it if you explain you need it for Laravel
- Makes future deployments much easier

---

## 🚨 IF SOMETHING GOES WRONG

### Restore Backup:

1. **File Manager:**
   - Delete new `hct` folder
   - Rename `hct_backup_20jan2026` → `hct`

2. **Database:**
   - phpMyAdmin → Import
   - Choose `backup_20jan2026.sql`
   - Click Go

3. **Clear cache:**
   - File Manager → `hct/bootstrap/cache/`
   - Delete all `.php` files
   - `hct/storage/framework/cache/`
   - Delete all cache files

---

## ✅ FINAL CHECKLIST

### Before Deployment:
- [ ] Created `deploy.php` file
- [ ] Changed SECRET_KEY to random string
- [ ] Backed up production database
- [ ] Compressed project (without vendor, node_modules, .env)

### During Deployment:
- [ ] Renamed old production folder (don't delete!)
- [ ] Uploaded and extracted new files
- [ ] Copied production `.env` file
- [ ] Installed composer dependencies
- [ ] Ran deploy.php?action=cache-clear
- [ ] Ran deploy.php?action=migrate
- [ ] Ran deploy.php?action=seed-tax
- [ ] Ran deploy.php?action=optimize
- [ ] **DELETED deploy.php and install.php files!**

### After Deployment:
- [ ] Tested login
- [ ] Checked employee data intact
- [ ] Tested new features
- [ ] Monitored for errors
- [ ] Can delete backup folder after 1 week

---

## 📝 deploy.php Template (Copy This)

Save this as `deploy.php` in your project root before zipping:

```php
<?php
define('SECRET_KEY', 'hct_secret_xyz123_change_me'); // ⚠️ Change this!

if (!isset($_GET['key']) || $_GET['key'] !== SECRET_KEY) {
    die('❌ Access Denied');
}

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$action = $_GET['action'] ?? 'help';

echo "<!DOCTYPE html><html><head><title>Deploy</title></head><body>";
echo "<h1>🚀 Deployment Script</h1><pre>";

switch($action) {
    case 'migrate':
        echo "Running migrations...\n";
        $kernel->call('migrate', ['--force' => true]);
        echo "\n✅ Done!\n";
        break;
    case 'seed-tax':
        echo "Seeding tax brackets...\n";
        $kernel->call('db:seed', ['--class' => 'TaxBracketSeeder', '--force' => true]);
        echo "\n✅ Done!\n";
        break;
    case 'cache-clear':
        echo "Clearing caches...\n";
        $kernel->call('config:clear');
        $kernel->call('cache:clear');
        $kernel->call('view:clear');
        $kernel->call('route:clear');
        echo "\n✅ Done!\n";
        break;
    case 'optimize':
        echo "Optimizing...\n";
        $kernel->call('config:cache');
        $kernel->call('route:cache');
        echo "\n✅ Done!\n";
        break;
    case 'status':
        $kernel->call('migrate:status');
        break;
    default:
        echo "Actions: migrate, seed-tax, cache-clear, optimize, status\n";
        echo "Usage: deploy.php?action=migrate&key=YOUR_KEY\n";
}

echo "</pre>";
echo "<h2 style='color:red;'>⚠️ DELETE THIS FILE AFTER USE!</h2>";
echo "</body></html>";
?>
```

---

**You're all set! No terminal needed!** 🎉

Just follow the steps, and you can deploy safely using only cPanel File Manager and your browser!
