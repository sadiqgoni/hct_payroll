# cPanel SSH Access Setup Guide

## Current Status
- **Server**: business158.web-hosting.com
- **Username**: kssbonline
- **Domain**: kssbonline.org
- **IP Address**: 162.0.217.138
- **Issue**: Connection timeout on all SSH attempts

## Step 1: Enable SSH in cPanel

### Access cPanel
1. Go to: `https://kssbonline.org/cpanel` or `https://kssbonline.org:2083`
2. Login with your cPanel credentials

### Enable SSH Access
1. In cPanel, scroll to **Security** section
2. Click on **"SSH Access"** or **"Terminal"**
3. Look for **"Manage SSH Keys"**

## Step 2: Generate SSH Key on Your Mac

Open Terminal and run:

```bash
# Generate SSH key pair
ssh-keygen -t rsa -b 4096 -f ~/.ssh/kssbonline_rsa -C "kssbonline@kssbonline.org"

# Press Enter when asked for passphrase (or set one if you prefer)

# View your public key (you'll need this for cPanel)
cat ~/.ssh/kssbonline_rsa.pub
```

**Important**: Copy the entire output of the `cat` command. It should start with `ssh-rsa` and end with your email.

## Step 3: Import Public Key to cPanel

1. In cPanel's **SSH Access** page, click **"Manage SSH Keys"**
2. Click **"Import Key"** or look for an option to add a public key
3. Paste your public key from Step 2
4. Give it a name: `macbook-pro-2026`
5. Click **"Import"** or **"Save"**
6. If you see the key in a list, click **"Authorize"** next to it

## Step 4: Create SSH Config File

Create/edit the SSH config on your Mac:

```bash
nano ~/.ssh/config
```

Add this configuration:

```
Host kssbonline
    HostName kssbonline.org
    User kssbonline
    Port 22
    IdentityFile ~/.ssh/kssbonline_rsa
    ServerAliveInterval 60
    ServerAliveCountMax 3
    
Host kssbonline-ip
    HostName 162.0.217.138
    User kssbonline
    Port 22
    IdentityFile ~/.ssh/kssbonline_rsa
    ServerAliveInterval 60
    ServerAliveCountMax 3
```

Save with: `Ctrl+O`, `Enter`, then `Ctrl+X`

## Step 5: Set Correct Permissions

```bash
chmod 700 ~/.ssh
chmod 600 ~/.ssh/kssbonline_rsa
chmod 644 ~/.ssh/kssbonline_rsa.pub
chmod 600 ~/.ssh/config
```

## Step 6: Test Connection

Try connecting with different methods:

```bash
# Method 1: Using the config alias
ssh kssbonline

# Method 2: If port 22 doesn't work, try 2222
ssh -p 2222 kssbonline

# Method 3: Try alternative port 21098 (common for cPanel)
ssh -p 21098 kssbonline@kssbonline.org

# Method 4: Direct connection
ssh -i ~/.ssh/kssbonline_rsa kssbonline@kssbonline.org
```

## Step 7: Contact Hosting Provider

If all attempts fail with timeout, **you MUST contact your hosting provider** support and ask:

### Questions to Ask Support:
1. "Is SSH access enabled for my account username: kssbonline?"
2. "What SSH port should I use? (22, 2222, 21098, or custom?)"
3. "Do I need to enable SSH access from my cPanel account first?"
4. "Is there an IP whitelist for SSH connections?"
5. "Are there firewall restrictions preventing SSH access?"

### Provide This Info to Support:
- Account username: `kssbonline`
- Server: `business158.web-hosting.com`
- Your IP address: (check at https://whatismyip.com)
- Error: "Connection timed out on port 22, 2222"

## Alternative: Use cPanel Terminal

If SSH from your machine isn't available or takes time to enable:

1. In cPanel, look for **"Terminal"** in the **Advanced** section
2. Click to open the web-based terminal
3. You'll have command-line access in your browser
4. You can run:
   ```bash
   cd public_html
   php artisan migrate
   php artisan cache:clear
   composer install
   ```

## Common SSH Ports for cPanel Hosting:
- **Standard**: 22
- **Alternative**: 2222
- **cPanel specific**: 21098
- **Custom**: Ask your provider

## Troubleshooting

### Connection Timeout
- SSH might not be enabled on your account
- Firewall blocking your IP
- Wrong port number
- Hosting provider disabled SSH for shared hosting

### Permission Denied
- Public key not authorized in cPanel
- Wrong username
- Key permissions incorrect (fix with chmod commands above)

### Host Key Verification Failed
```bash
ssh-keygen -R kssbonline.org
ssh-keygen -R 162.0.217.138
# Then try connecting again
```

## Next Steps: Automated Deployment

Once SSH is working, you can:
1. Use rsync for file synchronization
2. Create deployment scripts
3. Run migrations remotely
4. Avoid manual file uploads

See `DEPLOYMENT_SSH_SIMPLE.md` for automated deployment setup.

## Quick Reference

```bash
# Connect to server
ssh kssbonline

# Upload files
scp -r local_folder/* kssbonline:~/public_html/

# Run remote command
ssh kssbonline "cd public_html && php artisan migrate"

# Sync files (better than zip upload)
rsync -avz --exclude 'vendor' --exclude 'node_modules' ./ kssbonline:~/public_html/
```

## Security Notes

1. Never share your private key (`~/.ssh/kssbonline_rsa`)
2. Only upload the public key (`.pub` file) to servers
3. Use passphrase for extra security
4. Regularly rotate your SSH keys
5. Keep cPanel session logged out when not in use

## Need Help?

If you're still stuck after:
1. Trying all SSH ports
2. Importing your public key in cPanel
3. Contacting hosting support

Let me know and we'll find an alternative deployment solution!
