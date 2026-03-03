# Gmail Setup for Password Reset - Quick Guide

## 🎯 What You Need
- A Gmail account
- 2-Factor Authentication enabled
- App password (16 characters)

## 📋 Step-by-Step Setup

### Step 1: Enable 2-Factor Authentication
1. Go to: https://myaccount.google.com/
2. Click **Security** (left menu)
3. Find **2-Step Verification** → Click **Turn on**
4. Follow the setup process

### Step 2: Generate App Password
1. In Google Account → **Security** → **App passwords**
2. Click **Select app** → Choose **Mail**
3. Click **Select device** → Choose **Other (Custom name)**
4. Enter name: `NexusPlay`
5. Click **Generate**
6. **Copy the 16-character password** (example: `abcd efgh ijkl mnop`)

### Step 3: Update .env File
Replace line 43 in your `.env` file:

```bash
# Before:
MAILER_DSN=gmail://YOUR-EMAIL@gmail.com:YOUR-APP-PASSWORD@default

# After (example):
MAILER_DSN=gmail://john.doe@gmail.com:abcd efgh ijkl mnop@default
```

**Important:**
- Replace `YOUR-EMAIL@gmail.com` with your actual Gmail
- Replace `YOUR-APP-PASSWORD` with the 16-character password
- **Keep the spaces in the app password!**

### Step 4: Clear Cache
```bash
php bin/console cache:clear
```

## 🧪 Test It

1. Go to: `http://localhost:8000/forgot-password`
2. Enter your email address
3. Check your Gmail inbox
4. Click the reset link in the email
5. Set new password

## 🔧 Troubleshooting

### "Authentication failed" error:
- Double-check app password (copy-paste exactly)
- Ensure 2FA is enabled on your Gmail account
- Make sure you're using an App password, not your regular password

### "No email received":
- Check spam folder
- Verify the email address exists in your database
- Check Symfony logs: `php bin/console log:tail`

### Alternative: Use MailHog for development
If you don't want to send real emails during development:

1. Comment out Gmail line in .env:
```bash
# MAILER_DSN=gmail://YOUR-EMAIL@gmail.com:YOUR-APP-PASSWORD@default
```

2. Uncomment MailHog line:
```bash
MAILER_DSN=smtp://127.0.0.1:1025
```

3. Install MailHog:
```bash
docker run -p 1025:1025 -p 8025:8025 mailhog/mailhog
```

4. View emails at: http://localhost:8025

## ✅ Success Indicators
- ✅ Forgot password form loads
- ✅ Email arrives in Gmail inbox
- ✅ Reset link works (valid for 30 minutes)
- ✅ Password updates successfully
- ✅ Can login with new password

---

**🎉 That's it! Your password reset will now work with Gmail!**
