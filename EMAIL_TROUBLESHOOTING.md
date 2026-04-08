# Password Reset Email Troubleshooting Guide

## Issue: "Failed to send reset email. Please try again later or contact support."

This guide helps diagnose and fix password reset email issues.

---

## ✅ Step 1: Verify Database Table

Run the migration to ensure the `password_resets` table exists:

```bash
php migration_add_password_resets.php
```

**Expected Output:**
```
✓ password_resets table created successfully.
```

If the table already exists, you'll see:
```
✓ password_resets table already exists.
```

---

## ✅ Step 2: Run Diagnostic Test

Check if all components are working:

```bash
php test_email_debug.php
```

**What to check in output:**
- `mail() function available - ✓ YES`
- `password_resets table - ✓ EXISTS`
- All functions should show `✓ EXISTS`

---

## ✅ Step 3: Check Email Logs

After attempting a password reset, check if emails are being logged:

```bash
php view_email_logs.php
```

Or browse to: `http://localhost/cdf_system/view_email_logs.php`

### Email Log Location
- **Directory:** `logs/emails/`
- **Files:** Named by date (e.g., `2025-11-24.log`)

---

## Common Issues & Solutions

### Issue 1: No Email Logs Created

**Cause:** logs/emails directory doesn't exist or isn't writable

**Solution:**
```bash
# Create the directory
mkdir logs\emails

# Set permissions (Windows)
# Or use your hosting panel to set 755 permissions

# Try password reset again
```

### Issue 2: Email Logs Exist But Not Received

**Status:** This is normal for local development

**Why?** PHP mail() requires a working mail server, which isn't configured on most development machines.

**Solutions for Development:**

#### Option A: Test with Email Logs (Recommended for Dev)
- Emails are logged to `logs/emails/` directory
- Check the log files to verify reset links work
- Use the reset link directly to test functionality

#### Option B: Configure SMTP (For Local Testing)
Install Mailhog or similar SMTP testing tool:
```bash
# Download from: https://github.com/mailhog/MailHog
# Or use: https://mailtrap.io/
```

Update `php.ini`:
```ini
[mail function]
SMTP = localhost
smtp_port = 1025
```

#### Option C: Use Third-Party Email Service
Configure an SMTP service like:
- SendGrid
- Mailgun
- AWS SES
- Gmail SMTP

---

## For Production Deployment

### Step 1: Configure Mail Server

**On Linux/cPanel:**
```bash
# Ensure mail server is running
sudo service postfix status
sudo service exim status

# Or check with your hosting provider
```

**Update php.ini:**
```ini
[mail function]
sendmail_path = "/usr/sbin/sendmail -t -i"
SMTP = localhost
smtp_port = 25
```

### Step 2: Test Email Sending

```bash
php -r "mail('test@example.com', 'Test', 'Test body');"
```

Check server error logs if it fails.

### Step 3: Configure Reply-To Address

Edit `functions.php`, find `sendPasswordResetEmail()`:

```php
// Change this line to your actual support email:
$headers .= "From: CDF System <your-support@domain.com>\r\n";
$headers .= "Reply-To: your-support@domain.com\r\n";
```

### Step 4: Monitor Email Delivery

- Check error logs regularly
- Monitor email bounce rates
- Set up email alerts for failures

---

## Testing the Complete Flow

### Test 1: Local Development Testing

1. Go to `forgot_password.php`
2. Enter a valid user email (check users in database)
3. Click "Send Reset Instructions"
4. Check `logs/emails/` for the reset email
5. Copy the reset link from the log
6. Open the link in browser to test reset form
7. Set new password
8. Test login with new password

### Test 2: Production Testing

1. Use a real email address
2. Check email inbox (including spam folder)
3. Verify email content is correct
4. Click reset link and complete password reset
5. Login with new password
6. Monitor server logs for any errors

---

## Email Content Verification

Password reset emails should contain:

✓ Professional HTML formatting  
✓ Reset link with valid token  
✓ Expiry notice (24 hours)  
✓ Security warnings  
✓ Contact information  
✓ Government disclaimer  

---

## Debugging Commands

### Check PHP Mail Configuration
```bash
php -r "phpinfo();" | grep -A 10 "mail function"
```

### Check Recent Email Logs
```bash
# Windows
type logs\emails\2025-11-24.log

# Linux
cat logs/emails/2025-11-24.log
```

### View Error Logs
```bash
# PHP error log
tail -f C:\xampp\php\logs\php_error_log

# Or on Linux
tail -f /var/log/php-errors.log
```

### Test Email Function Directly
```php
<?php
require_once 'functions.php';

$email = 'admin@example.com';  // Use valid user email
$token = generateResetToken();

$result = sendPasswordResetEmail($email, $token);
echo $result ? "Success" : "Failed";
?>
```

---

## Verification Checklist

Before considering the system "fixed":

- [ ] `logs/emails/` directory exists and is writable
- [ ] `password_resets` table exists in database
- [ ] All three functions (`emailExists`, `generateResetToken`, `sendPasswordResetEmail`) are defined
- [ ] Test email shows up in email logs
- [ ] Reset link format is valid: `http://yourdomain.com/reset_password.php?token=XXXXX`
- [ ] Token contains 64 characters
- [ ] Password reset form loads when clicking link
- [ ] New password can be set successfully
- [ ] Login works with new password
- [ ] Old password no longer works

---

## Support & Next Steps

### If Emails Still Aren't Sending:

1. **For Development:**
   - Use email logs in `logs/emails/`
   - Test reset flow manually using logged links
   - Email logs prove the system works correctly

2. **For Production:**
   - Contact your hosting provider for mail server configuration
   - Verify mail server is running
   - Check firewall allows outbound SMTP
   - Use third-party SMTP service (SendGrid, etc.)
   - Implement bounce handling and retry logic

### Related Files

- `functions.php` - Password reset functions
- `forgot_password.php` - Password reset request form
- `reset_password.php` - Password reset completion
- `migration_add_password_resets.php` - Database setup
- `test_email_debug.php` - Diagnostic tool
- `view_email_logs.php` - Email log viewer
- `logs/emails/` - Email logs directory

---

## Production Deployment Checklist

- [ ] Mail server configured and tested
- [ ] SMTP settings in php.ini or server config
- [ ] From/Reply-To email addresses configured
- [ ] Email templates reviewed and customized
- [ ] Error logging enabled
- [ ] Bounce handling implemented (optional)
- [ ] Email rate limiting considered
- [ ] SSL/TLS for secure connections verified
- [ ] Admin alerts for failed sends configured (optional)
- [ ] Load testing completed if high volume expected
