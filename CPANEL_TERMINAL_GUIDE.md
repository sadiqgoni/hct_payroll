# Using cPanel Terminal (Alternative to SSH)

If you can't get SSH working from your local machine, cPanel provides a web-based Terminal that gives you command-line access directly in your browser.

## Accessing cPanel Terminal

### Step 1: Login to cPanel
1. Go to: `https://kssbonline.org/cpanel` or `https://kssbonline.org:2083`
2. Login with your cPanel credentials

### Step 2: Open Terminal
1. Scroll down to the **Advanced** section
2. Click on **"Terminal"**
3. A terminal window will open in your browser

## Common Laravel Commands in cPanel Terminal

### Navigate to Your Application
```bash
cd ~/public_html
# or if your app is in a subdirectory
cd ~/public_html/your-app-folder
```

### Run Migrations
```bash
php artisan migrate --force
```

The `--force` flag is needed in production environment.

### Clear Caches
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Optimize for Production
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### Install/Update Composer Dependencies
```bash
composer install --optimize-autoloader --no-dev
# or
composer update --no-dev
```

### Fix Permissions
```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
chown -R kssbonline:kssbonline storage
chown -R kssbonline:kssbonline bootstrap/cache
```

### View Logs
```bash
tail -n 50 storage/logs/laravel.log
# or
cat storage/logs/laravel.log
```

### Check PHP Version
```bash
php -v
```

### Check Laravel Version
```bash
php artisan --version
```

### List All Artisan Commands
```bash
php artisan list
```

## Deployment Workflow Using cPanel Terminal

### Option 1: Upload & Extract (Current Method - Improved)

1. **On Your Local Machine:**
   ```bash
   # Run the deployment script
   ./deploy-to-cpanel.sh
   ```

2. **In cPanel File Manager:**
   - Upload the generated ZIP file
   - Extract it to `public_html/`

3. **In cPanel Terminal:**
   ```bash
   cd ~/public_html
   composer install --optimize-autoloader --no-dev
   php artisan migrate --force
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   chmod -R 775 storage bootstrap/cache
   ```

### Option 2: Git Pull (Recommended if Git is available)

1. **Setup Git Repository (One-time)**
   ```bash
   cd ~/public_html
   git init
   git remote add origin https://github.com/yourusername/your-repo.git
   ```

2. **Deploy Updates:**
   ```bash
   cd ~/public_html
   git pull origin main
   composer install --optimize-autoloader --no-dev
   php artisan migrate --force
   php artisan cache:clear
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

## Database Management

### Run Specific Migration
```bash
php artisan migrate --path=/database/migrations/2026_01_17_142118_create_tax_brackets_table.php --force
```

### Rollback Last Migration
```bash
php artisan migrate:rollback --force
```

### Refresh All Migrations (⚠️ Dangerous - Deletes All Data!)
```bash
php artisan migrate:fresh --force --seed
```

### Run Seeders
```bash
php artisan db:seed --class=TaxBracketSeeder --force
```

### Check Database Connection
```bash
php artisan tinker
# Then type:
DB::connection()->getPdo();
# Press Ctrl+C to exit
```

## Environment Management

### Edit .env File
```bash
nano .env
# or
vi .env
```

**Important .env settings for production:**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://kssbonline.org

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password
```

After editing .env:
```bash
php artisan config:cache
```

## Troubleshooting Common Issues

### Issue: "Permission denied" errors
```bash
chmod -R 775 storage bootstrap/cache
chown -R kssbonline:kssbonline storage bootstrap/cache
```

### Issue: "Class not found" errors
```bash
composer dump-autoload
php artisan clear-compiled
php artisan optimize
```

### Issue: "Session not working"
```bash
php artisan session:table
php artisan migrate --force
php artisan config:cache
```

### Issue: "Route not found"
```bash
php artisan route:clear
php artisan route:cache
```

### Issue: "View not found"
```bash
php artisan view:clear
php artisan view:cache
```

### Issue: Check if services are running
```bash
php artisan queue:work --daemon
# Check if any background jobs are needed
```

## File Management Commands

### List Files
```bash
ls -la
```

### Create Backup
```bash
cd ~
tar -czf backup_$(date +%Y%m%d).tar.gz public_html/
```

### Check Disk Space
```bash
du -sh public_html/
df -h
```

### Find Large Files
```bash
cd ~/public_html
find . -type f -size +10M -exec ls -lh {} \;
```

### Clean Old Logs
```bash
cd ~/public_html/storage/logs
> laravel.log  # Empties the file
# or
rm laravel-*.log  # Removes old rotated logs
```

## Monitoring & Debugging

### Watch Live Logs
```bash
tail -f storage/logs/laravel.log
# Press Ctrl+C to stop
```

### Check Laravel Queue
```bash
php artisan queue:work --stop-when-empty
```

### Test Email Configuration
```bash
php artisan tinker
# Then:
Mail::raw('Test email', function($msg) {
    $msg->to('your@email.com')->subject('Test');
});
```

## Quick Deployment Script

Create this file on the server:

```bash
nano ~/deploy.sh
```

Add this content:

```bash
#!/bin/bash
cd ~/public_html
echo "🚀 Starting deployment..."
git pull origin main
composer install --optimize-autoloader --no-dev
php artisan migrate --force
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
chmod -R 775 storage bootstrap/cache
echo "✅ Deployment complete!"
```

Make it executable:
```bash
chmod +x ~/deploy.sh
```

Then deploy with:
```bash
~/deploy.sh
```

## Pro Tips

1. **Keep Terminal Open**: The cPanel terminal session might timeout. Keep it active or refresh if needed.

2. **Use Screen/Tmux**: If available, use screen to keep long-running processes:
   ```bash
   screen -S myapp
   # Do your work
   # Press Ctrl+A then D to detach
   # screen -r myapp to reattach
   ```

3. **Alias Commands**: Create shortcuts in `~/.bashrc`:
   ```bash
   echo "alias art='php artisan'" >> ~/.bashrc
   echo "alias cc='php artisan cache:clear'" >> ~/.bashrc
   source ~/.bashrc
   ```

4. **Check PHP Memory**: If composer fails:
   ```bash
   php -d memory_limit=-1 /usr/local/bin/composer install
   ```

5. **Backup Before Changes**:
   ```bash
   cp .env .env.backup
   php artisan down  # Maintenance mode
   # Do your updates
   php artisan up    # Back online
   ```

## Security Reminders

1. Never commit `.env` file to Git
2. Keep `APP_DEBUG=false` in production
3. Regularly update dependencies: `composer update --no-dev`
4. Monitor logs for suspicious activity
5. Keep backups of database and files

## Need More Help?

If you need to do something specific that's not covered here, let me know and I'll add it to this guide!

## Comparison: cPanel Terminal vs Local SSH

| Feature | cPanel Terminal | Local SSH |
|---------|----------------|-----------|
| Access | Web browser | Command line |
| Speed | Slower (web) | Faster |
| File Upload | Use File Manager | rsync/scp |
| Automation | Limited | Full scripting |
| Multiple Sessions | New browser tab | Multiple terminals |
| Persistence | Session timeout | Stable |

**Recommendation**: Try to get SSH working for long-term efficiency, but cPanel Terminal works perfectly fine for day-to-day maintenance!
