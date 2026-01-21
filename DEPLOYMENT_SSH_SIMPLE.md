# 🚀 SIMPLE SSH DEPLOYMENT GUIDE

## ✅ You Have SSH Access - This is EASY!

Since you have SSH access in cPanel, deployment is much simpler and safer!

---

## 📋 ONE-TIME SSH SETUP (Do this once)

### Step 1: Generate SSH Key in cPanel

1. **cPanel** → **SSH Access** → **Manage SSH Keys**
2. Click **Generate a New Key**
3. Fill in:
   - Key Name: `hct_deployment`
   - Password: (leave blank for no password)
   - Key Type: RSA
   - Key Size: 2048
4. Click **Generate Key**
5. Go back to **Manage SSH Keys**
6. Find your key → Click **Manage** → **Authorize**
7. ✅ Done!

### Step 2: Get SSH Login Details

In cPanel → **SSH Access** main page, you'll see:
```
Username: your_cpanel_username
Server: your-server.com or IP address
Port: 22 (usually)
```

### Step 3: Connect from Your Mac

Open Terminal on your Mac:

```bash
# Connect to your server
ssh your_cpanel_username@your-server.com

# If port is different (e.g., 2222):
ssh your_cpanel_username@your-server.com -p 2222

# Enter your cPanel password when prompted
```

✅ You're now in your production server!

---

## 🎯 SAFE DEPLOYMENT STEPS (Using SSH)

### Step 1: Backup Production Database

```bash
# While connected via SSH
cd ~/public_html  # or wherever your Laravel app is

# Backup database
php artisan db:backup  # If you have a backup package

# OR manually via mysqldump:
mysqldump -u YOUR_DB_USER -p YOUR_DB_NAME > backup_$(date +%Y%m%d).sql
# Enter your database password when prompted
```

✅ **Database backed up!**

---

### Step 2: Upload New Code

**Option A: Using FTP/FileZilla (Recommended for first time)**

1. Use **FileZilla** to connect
2. Upload only the files that changed:
   - `app/` folder
   - `database/migrations/` folder
   - `database/seeders/` folder  
   - `resources/views/` folder
   - `routes/` folder
   - `config/` folder
3. DON'T upload:
   - `vendor/` (will install via composer)
   - `.env` (production already has it)
   - `storage/logs/`
   - `node_modules/`

**Option B: Using rsync (Advanced)**

On your local Mac:
```bash
cd /Users/macbookpro/Downloads/hct

# Upload only changed files
rsync -avz --exclude 'vendor' --exclude 'node_modules' --exclude '.env' --exclude 'storage/logs' \
  ./ your_cpanel_username@your-server.com:~/public_html/
```

---

### Step 3: SSH into Production and Run Commands

```bash
# Connect via SSH
ssh your_cpanel_username@your-server.com

# Navigate to your Laravel app
cd ~/public_html  # or ~/hct or wherever your app is

# Install/Update composer dependencies
composer install --no-dev --optimize-autoloader

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Run migrations (SAFE - doesn't delete data!)
php artisan migrate --force

# Seed ONLY tax brackets (not all seeders!)
php artisan db:seed --class=TaxBracketSeeder --force

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Fix permissions (if needed)
chmod -R 755 storage bootstrap/cache
```

✅ **Deployment complete!**

---

### Step 4: Test Everything

1. Visit your production website
2. Login
3. Check:
   - ✅ Existing employee data intact
   - ✅ Tax brackets page works: `/admin/tax-brackets`
   - ✅ Group salary update shows new option
   - ✅ Deduction countdown works
4. Check logs for errors:
   ```bash
   tail -50 storage/logs/laravel.log
   ```

---

## 🚨 IF SOMETHING GOES WRONG (Rollback)

### Quick Rollback:

```bash
# Via SSH
cd ~/public_html

# Restore database
mysql -u YOUR_DB_USER -p YOUR_DB_NAME < backup_20260120.sql

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Check application
tail -50 storage/logs/laravel.log
```

---

## 📝 COMPLETE DEPLOYMENT CHECKLIST

```bash
# === BEFORE DEPLOYMENT ===
# 1. Backup production database
mysqldump -u DB_USER -p DB_NAME > backup_$(date +%Y%m%d).sql

# 2. Test on localhost (already done ✅)

# === DURING DEPLOYMENT ===
# 3. Upload new files (via FTP or rsync)

# 4. SSH into production
ssh your_cpanel_username@your-server.com

# 5. Navigate to app
cd ~/public_html

# 6. Install dependencies
composer install --no-dev --optimize-autoloader

# 7. Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# 8. Run migrations
php artisan migrate --force

# 9. Seed tax brackets (if first time)
php artisan db:seed --class=TaxBracketSeeder --force

# 10. Optimize
php artisan config:cache
php artisan route:cache

# === AFTER DEPLOYMENT ===
# 11. Test website
# 12. Check logs
tail -50 storage/logs/laravel.log

# 13. If all good, delete backup after 1 week
# If problem, restore from backup
```

---

## 🎯 WHAT YOU'RE DEPLOYING

### Files Changed:
- ✅ `app/Models/TaxBracket.php` (dynamic PAYE system)
- ✅ `app/DeductionCalculation.php` (uses dynamic tax)
- ✅ `app/Livewire/Forms/GroupSalaryUpdate.php` (new PAYE option)
- ✅ `app/Livewire/Forms/DeductionCountdown.php` (year rollover fix)
- ✅ `resources/views/livewire/forms/group-salary-update.blade.php` (removed Formula 1/2)
- ✅ `database/seeders/TaxBracketSeeder.php` (new tax data)

### Database Changes:
- ✅ New table: `tax_brackets`
- ✅ Migration: Creates the tax_brackets table
- ✅ No changes to existing tables
- ✅ No data loss

### Features Added:
- ✅ Dynamic tax bracket system
- ✅ No more hardcoded Formula 1/2
- ✅ Tax bracket management UI
- ✅ Deduction countdown year fix

---

## 💡 SAFETY TIPS

### ✅ DO:
- Backup database first (always!)
- Run `migrate` (it's safe)
- Run specific seeders (`--class=TaxBracketSeeder`)
- Test after deployment
- Keep backup for 1 week

### ❌ DON'T:
- Don't run `migrate:fresh` (deletes all data!)
- Don't run `migrate:refresh` (deletes all data!)
- Don't run `db:seed` without `--class` (seeds everything)
- Don't delete `.env` file
- Don't upload your local `.env`

---

## 🔐 SSH COMMANDS REFERENCE

### Connect to Server:
```bash
ssh your_username@your-server.com
```

### Navigate:
```bash
cd ~/public_html        # Go to web root
cd ~/hct               # Go to hct folder (if different)
pwd                    # Show current directory
ls -la                 # List files
```

### Laravel Commands:
```bash
php artisan migrate --force              # Run migrations
php artisan db:seed --class=ClassName    # Run specific seeder
php artisan config:clear                 # Clear config cache
php artisan cache:clear                  # Clear app cache
php artisan route:list                   # List all routes
php artisan tinker                       # Open Laravel console
```

### Database:
```bash
# Backup
mysqldump -u USER -p DATABASE > backup.sql

# Restore
mysql -u USER -p DATABASE < backup.sql

# Connect to MySQL
mysql -u USER -p DATABASE
```

### Files:
```bash
chmod -R 755 storage                     # Fix permissions
tail -50 storage/logs/laravel.log        # View logs
rm -rf bootstrap/cache/*.php             # Clear bootstrap cache
```

### Exit SSH:
```bash
exit
```

---

## 📱 QUICK DEPLOYMENT (TL;DR)

**On your Mac:**
```bash
# Upload files via FileZilla (exclude vendor, node_modules, .env)
```

**Via SSH:**
```bash
ssh username@server.com
cd ~/public_html
composer install --no-dev --optimize-autoloader
php artisan config:clear && php artisan cache:clear
php artisan migrate --force
php artisan db:seed --class=TaxBracketSeeder --force
php artisan config:cache && php artisan route:cache
exit
```

**Test website** → ✅ Done!

---

## 🎓 LEARNING RESOURCES

### First Time Using SSH?

**Tutorial:**
1. cPanel → SSH Access → Manage SSH Keys → Generate
2. Authorize the key
3. Open Mac Terminal
4. Type: `ssh username@server.com`
5. Enter password
6. You're in! 🎉

### Useful SSH Tricks:

**Create an alias for easy login:**
```bash
# On your Mac, edit ~/.ssh/config
nano ~/.ssh/config

# Add this:
Host hct-production
    HostName your-server.com
    User your_username
    Port 22

# Save and exit (Ctrl+X, Y, Enter)

# Now you can just type:
ssh hct-production
```

---

## ✅ FINAL ANSWER TO YOUR CONCERN

> "I don't want to temper the production"

**You won't! Here's why:**

1. ✅ **Database backup first** - If anything goes wrong, restore it
2. ✅ **`migrate` is safe** - Only ADDS tables/columns, never deletes
3. ✅ **Upload files separately** - Old files stay as backup
4. ✅ **Test after deployment** - Check everything works
5. ✅ **Rollback plan ready** - Restore backup if needed

**The migration will:**
- ✅ Create `tax_brackets` table (new, empty table)
- ✅ Leave all existing tables untouched
- ✅ Keep all employee data safe
- ✅ Keep all salary data safe

**Think of it like this:**
- You're adding a new drawer to a filing cabinet
- All the existing drawers stay exactly the same
- All the files in them are untouched
- You're just adding a new empty drawer for tax brackets

---

**You're safe to deploy!** SSH makes it even easier and safer! 🚀

Need help with any specific step? Just ask!
