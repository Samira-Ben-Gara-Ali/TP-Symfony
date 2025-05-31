# BookSaw Mailer Setup Guide

This project uses [Symfony Mailer](https://symfony.com/doc/current/mailer.html) to send emails (welcome, invoice/facture, etc.).

## 1. Gmail Setup (Recommended for Testing)

### a. Enable 2-Step Verification
- Go to [Google Account Security](https://myaccount.google.com/security)
- Enable **2-Step Verification** if not already enabled.

### b. Generate an App Password
- After enabling 2-Step Verification, go to **App Passwords** (in the same Security section)
- Select **Mail** as the app, and **Other (Custom name)** (e.g., "BookSaw")
- Google will generate a 16-character password (e.g., `abcd efgh ijkl mnop`)
- **Copy this password** (no spaces when used below)

## 2. Configure Your `.env.local`

Create or edit the file `.env.local` at the root of your project (never commit this file!). Add:

```
MAILER_DSN=smtp://your_gmail_address:your_app_password@smtp.gmail.com:587
MAILER_FROM=your_gmail_address
```

**Example:**
```
MAILER_DSN=smtp://drissia043@gmail.com:guezgbmlpvteicog@smtp.gmail.com:587
MAILER_FROM=drissia043@gmail.com
```

- Replace `your_gmail_address` with your real Gmail address
- Replace `your_app_password` with the 16-character app password (no spaces)

## 3. Clear Symfony Cache

```bash
php bin/console cache:clear
```

## 4. Test the Mailer
- Register a new user (should receive a welcome email)
- Place a real order (should receive a facture/invoice email)
- Check your Gmail **Sent** folder and the recipient's inbox (and spam folder)

## 5. Security Best Practices
- **Never commit your real credentials or `.env.local` to Git!**
- `.env.local` is in `.gitignore` by default.
- Use app passwords, not your main Gmail password.

## 6. Troubleshooting
- If you get authentication errors, double-check your app password and Gmail address.
- If you don't see emails, check your spam folder.
- For more help, see [Symfony Mailer Docs](https://symfony.com/doc/current/mailer.html) or [Gmail App Passwords Help](https://support.google.com/accounts/answer/185833?hl=fr)

---

**You are now ready to test email sending in BookSaw!** 