# 🚀 PRODUCTION DEPLOYMENT GUIDE

## ⚠️ The Problem:
- You have a **localhost** version with new features and database changes
- You have a **production** version with live data that cannot be lost
- You need to deploy new code WITHOUT losing production data

---

## ✅ SAFE DEPLOYMENT STRATEGY

### Step 1: Backup Production Database (CRITICAL!)

**Before doing ANYTHING, backup the production database:**

1. Login to cPanel → phpMyAdmin
2. Select the production database
3. Click "Export" tab
4. Choose "Quick" export method
5. Format: SQL
6. Click "Go"
7. Save the file as: `production_backup_20jan2026.sql`
8. **Keep this file safe!** This is your insurance.

---

### Step 2: Identify New Database Changes

**On your localhost, check what migrations you've created:**

```bash
# List all migration files
ls -la database/migrations/

# Find new ones (after the production was last deployed)
```

**New migrations you created:**
- `xxxx_create_tax_brackets_table.php` (if it exists)
- Any other new migration files

---

### Step 3: Deploy Code Files (WITHOUT touching database yet)

**Option A: Using cPanel File Manager**

1. Compress your localhost project:
   ```bash
   cd /Users/macbookpro/Downloads/hct
   zip -r hct_update_20jan2026.zip . -x "vendor/*" -x "node_modules/*" -x "storage/logs/*" -x ".env" -x "public/storage/*"
   ```

2. Upload `hct_update_20jan2026.zip` to cPanel
3. **IMPORTANT:** Before extracting, rename the old production folder:
   - Rename: `hct` → `hct_backup_20jan2026`
4. Extract the new zip file
5. **Don't delete the backup folder yet!**

**Option B: Using Git (RECOMMENDED)**

If you have Git access:
```bash
# On production server via SSH or cPanel Terminal
cd /path/to/production
git pull origin main
```

---

### Step 4: Copy Production .env File

**CRITICAL:** Your localhost `.env` won't work on production!

1. Go to `hct_backup_20jan2026` folder
2. Copy the `.env` file
3. Paste it into the new `hct` folder
4. This ensures production database credentials are correct

---

### Step 5: Install Dependencies

**On production (via cPanel Terminal or SSH):**

```bash
cd /path/to/hct

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

---

### Step 6: Run Migrations (SAFE - Won't Delete Data!)

**This is the KEY step for database changes:**

```bash
# This only ADDS new tables/columns, doesn't delete existing data
php artisan migrate --force

# If you get errors, DON'T PANIC:
# Check the error message and fix it
```

**What `migrate` does:**
- ✅ Creates new tables (like `tax_brackets`)
- ✅ Adds new columns to existing tables
- ❌ Does NOT delete existing tables
- ❌ Does NOT delete existing data

**Example:** If you created `tax_brackets` table locally, it will create the same table in production (empty, with no data).

---

### Step 7: Seed ONLY New Data (If Needed)

**WARNING:** Don't run all seeders! Only seed new tables.

```bash
# For tax brackets (new feature)
php artisan db:seed --class=TaxBracketSeeder

# This only adds data to tax_brackets table, doesn't touch other tables
```

**DON'T RUN:**
```bash
php artisan db:seed  # ❌ This will seed ALL tables and may cause duplicates!
```

---

### Step 8: Test Production

1. Visit your production URL
2. Login with admin account
3. Test new features:
   - Tax brackets management
   - Group salary update (new "Calculate PAYE Dynamic" option)
   - Deduction countdown month update
4. Check existing features still work
5. Verify existing employee data is intact

---

### Step 9: If Something Goes Wrong (Rollback Plan)

**If production breaks:**

1. **Restore old code:**
   ```bash
   # Delete new folder
   rm -rf hct
   
   # Restore backup
   mv hct_backup_20jan2026 hct
   ```

2. **Restore database (if migrations broke something):**
   - Go to phpMyAdmin
   - Select database
   - Click "Import"
   - Choose `production_backup_20jan2026.sql`
   - Click "Go"

3. Everything will be back to how it was!

---

## 🎯 SPECIFIC STEPS FOR YOUR PROJECT

### Migration Files to Check:

```bash
# On localhost, list migration files
ls -la database/migrations/
```

**Files you probably need to run on production:**
- Any file containing `tax_brackets`
- Any file you created after the last deployment

### New Features to Test:

1. **Tax Brackets:**
   - URL: `/admin/tax-brackets`
   - Make sure table exists
   - Create a test tax bracket

2. **Group Salary Update:**
   - URL: `/salary/update/center`
   - Check "Calculate PAYE (Dynamic)" option appears
   - Test with one employee first

3. **Deduction Countdown:**
   - URL: `/deduction/countdown`
   - Update month from 2025 to 2026
   - Verify countdown updates

---

## 📋 DEPLOYMENT CHECKLIST

### Before Deployment:
- [ ] Backup production database (phpMyAdmin Export)
- [ ] Test all features on localhost
- [ ] List all new migration files
- [ ] Identify new seeders needed
- [ ] Prepare rollback plan

### During Deployment:
- [ ] Rename old production folder (don't delete!)
- [ ] Upload new code
- [ ] Copy production `.env` file
- [ ] Run `composer install --no-dev`
- [ ] Clear all caches
- [ ] Run `php artisan migrate --force`
- [ ] Run specific seeders only (TaxBracketSeeder)

### After Deployment:
- [ ] Test login
- [ ] Test new features
- [ ] Test existing features
- [ ] Check employee data intact
- [ ] Monitor for errors (check `storage/logs/laravel.log`)

### If Success:
- [ ] Delete backup folder (after 1 week of stable operation)
- [ ] Update documentation
- [ ] Celebrate! 🎉

### If Failure:
- [ ] Restore old code folder
- [ ] Restore database backup
- [ ] Investigate error
- [ ] Fix on localhost
- [ ] Try deployment again

---

## 🔒 IMPORTANT SAFETY RULES

### ✅ DO:
- Always backup database before deployment
- Test on localhost first
- Run migrations (they're safe)
- Keep old code folder as backup
- Deploy during low-traffic hours
- Run specific seeders only

### ❌ DON'T:
- Don't delete production folder immediately
- Don't run `php artisan db:seed` (all seeders)
- Don't run `php artisan migrate:fresh` (deletes all data!)
- Don't run `php artisan migrate:refresh` (deletes all data!)
- Don't upload `.env` from localhost
- Don't skip database backup

---

## 🛠️ HANDLING MIGRATION CONFLICTS

**Scenario:** Migration already exists on production

**Error:** `Table 'tax_brackets' already exists`

**Solution:**
```bash
# Check which migrations have run
php artisan migrate:status

# If a migration shows as "Ran", it's already applied
# If it shows as "Pending", it needs to run
```

**If table exists but migration is pending:**
```bash
# Mark migration as run without executing it
php artisan migrate --pretend
# Then manually mark it in migrations table (via phpMyAdmin)
```

---

## 📊 DATABASE SYNC STRATEGY

### Option 1: Production → Localhost (For Testing)

**When you want fresh production data on localhost:**

```bash
# 1. Export production database (phpMyAdmin)
# 2. Import to localhost:
mysql -u root -p your_local_db < production_backup.sql

# 3. Run any new migrations:
php artisan migrate
```

### Option 2: Localhost → Production (For New Features Only!)

**Only migrate new tables/columns, not data:**

```bash
# On production:
php artisan migrate --force

# Then seed ONLY new tables:
php artisan db:seed --class=TaxBracketSeeder
```

---

## 🚨 EMERGENCY CONTACTS

**If deployment fails badly:**

1. **Restore database immediately:**
   - phpMyAdmin → Import → `production_backup_20jan2026.sql`

2. **Restore old code:**
   - Rename new folder
   - Rename backup folder back to original name

3. **Clear all caches:**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan view:clear
   ```

4. **Contact hosting support** if site still down

---

## 📝 POST-DEPLOYMENT NOTES

**After successful deployment, document:**

1. What was deployed:
   - Date: 20 Jan 2026
   - Features: Dynamic Tax System, Deduction Countdown Fix
   - Migrations: tax_brackets table

2. What was tested:
   - Tax bracket creation
   - PAYE calculation with new rates
   - Group salary update
   - Deduction countdown month update

3. Known issues (if any):
   - None expected

4. Next deployment date:
   - Plan for next features

---

## 🎓 LEARNING FOR NEXT TIME

### Use Version Control (Git):

**Set up Git for easier deployments:**

```bash
# On localhost
git init
git add .
git commit -m "Initial commit"
git remote add origin your-repository-url
git push -u origin main

# On production
git clone your-repository-url
# Future deployments: just `git pull`
```

### Use Laravel Forge or Envoyer:

**Paid services that automate deployment:**
- Zero-downtime deployments
- Automatic backups
- Easy rollbacks
- Worth the cost for production apps

---

## ✅ SUMMARY

**Safe Deployment Steps:**
1. Backup production database ← MOST IMPORTANT!
2. Upload new code (keep old folder)
3. Copy production `.env`
4. Run `composer install`
5. Run `php artisan migrate` (safe, doesn't delete data)
6. Run specific seeders (TaxBracketSeeder only)
7. Test everything
8. If broken: restore backup
9. If working: celebrate and delete backup after 1 week

**Key Principle:** Migrations ADD things, they don't DELETE existing data (unless you explicitly write code to delete).

**Your production data is safe as long as:**
- You backup first
- You use `migrate` (not `migrate:fresh` or `migrate:refresh`)
- You don't run all seeders
- You keep the old code folder as backup

---

**Good luck with your deployment! 🚀**

**Remember:** Take it slow, backup first, test after each step. You've got this! 💪
