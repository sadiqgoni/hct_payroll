# ⚡ Quick Reference Card

Copy and paste these commands when you need them!

---

## 🔐 SSH Connection Commands

### Generate SSH Key (Run Once)
```bash
ssh-keygen -t rsa -b 4096 -f ~/.ssh/kssbonline_rsa -C "kssbonline@kssbonline.org"
```

### View Your Public Key (For cPanel)
```bash
cat ~/.ssh/kssbonline_rsa.pub
```

### Connect to Server
```bash
# Try these in order:
ssh kssbonline@kssbonline.org
ssh -p 2222 kssbonline@kssbonline.org
ssh -p 21098 kssbonline@kssbonline.org
```

---

## 📦 Deployment Commands

### Create Deployment Package (Local Mac)
```bash
cd /Users/macbookpro/Downloads/hct
./deploy-to-cpanel.sh
```

### After Upload (cPanel Terminal)
```bash
cd ~/public_html
composer install --optimize-autoloader --no-dev
php artisan migrate --force
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
chmod -R 775 storage bootstrap/cache
```

---

## 🚀 Most Used Artisan Commands

### Clear Everything
```bash
php artisan optimize:clear
```

### Run Migrations
```bash
php artisan migrate --force
```

### Cache Everything
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Clear Individual Caches
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Maintenance Mode
```bash
php artisan down               # Enable
php artisan up                 # Disable
```

---

## 🔍 Troubleshooting Commands

### Check Logs
```bash
tail -n 50 storage/logs/laravel.log
tail -f storage/logs/laravel.log  # Live watching
```

### Fix Permissions
```bash
chmod -R 775 storage bootstrap/cache
chown -R kssbonline:kssbonline storage bootstrap/cache
```

### Fix Composer Issues
```bash
composer dump-autoload
php artisan clear-compiled
```

### Check Versions
```bash
php -v
php artisan --version
composer --version
```

---

## 📂 File Management

### Navigate to App
```bash
cd ~/public_html
```

### List Files
```bash
ls -la
```

### Create Backup
```bash
cd ~
tar -czf backup_$(date +%Y%m%d_%H%M%S).tar.gz public_html/
```

### Check Disk Space
```bash
du -sh public_html/
df -h
```

---

## 🗄️ Database Commands

### Run Specific Migration
```bash
php artisan migrate --path=/database/migrations/YYYY_MM_DD_HHMMSS_migration_name.php --force
```

### Rollback Last Migration
```bash
php artisan migrate:rollback --step=1 --force
```

### Run Seeder
```bash
php artisan db:seed --class=TaxBracketSeeder --force
```

### Open Tinker (Database Console)
```bash
php artisan tinker
# Then try: DB::connection()->getPdo();
# Exit with: exit or Ctrl+D
```

---

## 📤 File Upload Commands (If SSH Works)

### Upload Single File
```bash
scp local-file.php kssbonline@kssbonline.org:~/public_html/
```

### Upload Directory
```bash
scp -r local-folder kssbonline@kssbonline.org:~/public_html/
```

### Sync with Rsync (Best Method)
```bash
rsync -avz --exclude 'vendor' --exclude 'node_modules' --exclude '.git' ./ kssbonline@kssbonline.org:~/public_html/
```

### Run Remote Command
```bash
ssh kssbonline@kssbonline.org "cd public_html && php artisan migrate --force"
```

---

## 🔧 Composer Commands

### Install Production Dependencies
```bash
composer install --optimize-autoloader --no-dev
```

### Update Dependencies
```bash
composer update --no-dev
```

### Dump Autoload
```bash
composer dump-autoload -o
```

### Require New Package
```bash
composer require vendor/package --no-dev
```

---

## 📝 .env Quick Edit

### View .env
```bash
cat .env | grep -i app
cat .env | grep -i db
```

### Edit .env
```bash
nano .env  # or vi .env
```

### After Editing .env, Always Run:
```bash
php artisan config:cache
```

---

## 🧹 Cleanup Commands

### Clear Old Logs
```bash
> storage/logs/laravel.log  # Empty log file
rm storage/logs/laravel-*.log  # Remove old rotated logs
```

### Clean Cache Files
```bash
rm -rf bootstrap/cache/*.php
rm -rf storage/framework/cache/*
rm -rf storage/framework/sessions/*
rm -rf storage/framework/views/*
```

### Remove Old Backups
```bash
find ~ -name "backup_*.tar.gz" -mtime +30 -delete
```

---

## 🎯 Common Workflows

### Complete Deployment
```bash
cd ~/public_html
git pull origin main  # If using git
composer install --optimize-autoloader --no-dev
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
chmod -R 775 storage bootstrap/cache
```

### Emergency Fix
```bash
php artisan down
# Make your fixes
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan up
```

### Performance Optimization
```bash
composer install --optimize-autoloader --no-dev --classmap-authoritative
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

---

## 🚨 Emergency Commands

### Site is Down?
```bash
php artisan up
```

### 500 Error?
```bash
chmod -R 775 storage bootstrap/cache
php artisan optimize:clear
tail -n 100 storage/logs/laravel.log
```

### Migrations Failed?
```bash
php artisan migrate:rollback --force
# Fix the migration file
php artisan migrate --force
```

### Session Issues?
```bash
php artisan session:table
php artisan migrate --force
php artisan config:cache
```

---

## 📱 cPanel Quick Links

### Access Points
```
cPanel Login:     https://kssbonline.org/cpanel
Alternative:      https://kssbonline.org:2083
File Manager:     cPanel → Files → File Manager
Terminal:         cPanel → Advanced → Terminal
SSH Access:       cPanel → Security → SSH Access
Databases:        cPanel → Databases → phpMyAdmin
```

### Server Info
```
Server:           business158.web-hosting.com
IP Address:       162.0.217.138
Username:         kssbonline
Home Directory:   /home/kssbonline
Public Directory: /home/kssbonline/public_html
```

---

## 💾 Backup Commands

### Quick Backup Before Changes
```bash
cd ~
tar -czf backup_before_$(date +%Y%m%d_%H%M%S).tar.gz public_html/
```

### Backup Database (in cPanel Terminal)
```bash
# Find your DB credentials in .env first
mysqldump -u your_db_user -p your_database_name > backup_$(date +%Y%m%d).sql
```

### Download Backup (if SSH works)
```bash
scp kssbonline@kssbonline.org:~/backup_*.tar.gz ~/Downloads/
```

---

## 🎓 Learning Resources

### Check Available Artisan Commands
```bash
php artisan list
```

### Get Help for Specific Command
```bash
php artisan help migrate
```

### View Routes
```bash
php artisan route:list
```

### View Config
```bash
php artisan config:show
```

---

## 🔗 Useful One-Liners

### Check if App is Up
```bash
curl -I https://kssbonline.org
```

### Find Large Files
```bash
find . -type f -size +10M -exec ls -lh {} \;
```

### Count Files
```bash
find . -type f | wc -l
```

### Check PHP Memory Limit
```bash
php -r "echo ini_get('memory_limit').PHP_EOL;"
```

### Test Database Connection
```bash
php artisan tinker --execute="var_dump(DB::connection()->getPdo());"
```

---

## 📋 Pre-Flight Checklist

Before every deployment, check:

- [ ] Changes tested locally
- [ ] Database migrations ready
- [ ] .env file backed up
- [ ] Recent backup created
- [ ] Low traffic time chosen
- [ ] Rollback plan ready

After every deployment, run:

- [ ] `php artisan migrate --force`
- [ ] `php artisan optimize:clear`
- [ ] `php artisan config:cache`
- [ ] Check logs for errors
- [ ] Test main functionality
- [ ] Verify database changes

---

## 🎁 Bonus: Create Aliases

Add to `~/.bashrc` or `~/.zshrc` on your Mac:

```bash
# Laravel aliases
alias art='php artisan'
alias migrate='php artisan migrate --force'
alias seed='php artisan db:seed --force'
alias cache-clear='php artisan optimize:clear'
alias cache-all='php artisan config:cache && php artisan route:cache && php artisan view:cache'

# Deployment aliases
alias deploy-prep='cd ~/Downloads/hct && ./deploy-to-cpanel.sh'
alias ssh-kssb='ssh kssbonline@kssbonline.org'
```

Then run: `source ~/.zshrc` (or `source ~/.bashrc`)

---

## 📞 Support Contact Info

**Hosting Provider Support:**
- Check your hosting provider's support portal
- Usually available in cPanel under "Support"
- Have your server name ready: business158

**Ask Support:**
1. "Is SSH enabled for my account?"
2. "What SSH port should I use?"
3. "How do I enable SSH access?"
4. "Can you help me set up SSH keys?"

---

## ✅ Daily Checklist

Morning:
```bash
ssh kssbonline@kssbonline.org
cd ~/public_html
tail -n 50 storage/logs/laravel.log
```

After Deployment:
```bash
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
tail -n 50 storage/logs/laravel.log
```

End of Day:
```bash
tar -czf ~/backup_$(date +%Y%m%d).tar.gz ~/public_html
```

---

**💡 Tip**: Bookmark this page and keep it handy. You'll refer to it often!

**🆘 Stuck?** Check `DEPLOYMENT_ACTION_PLAN.md` for detailed guides.
