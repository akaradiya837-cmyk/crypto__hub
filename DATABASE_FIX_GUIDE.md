# ⚠️ Database Not Updating - QUICK FIX GUIDE

## What's Happening?

The database is either:
1. Not connected (application is using JSON files instead)
2. Server not running
3. Tables not created
4. Connection error occurring silently

## ✅ IMMEDIATE FIX (Follow These Steps)

### Step 1: Start MySQL Server

1. **Open Laragon** (the application window)
2. Look for the **MySQL icon** (usually shows as a database icon)
3. **Click it to start MySQL**
4. Wait for it to show as "Running" (usually takes 5-10 seconds)

### Step 2: Run Database Setup

1. Go to this URL in your browser:
   ```
   http://localhost/sem%204th%20website/fix-database.php
   ```
   
   **OR** Copy paste:
   - `http://localhost/sem 4th website/fix-database.php`
   - Then replace spaces with `%20` if needed

2. This script will:
   - ✓ Check if MySQL is running
   - ✓ Create the database if it doesn't exist  
   - ✓ Create all required tables
   - ✓ Add cryptocurrency data
   - ✓ Test if everything works

3. Look for **"Database Setup Complete!"** message

### Step 3: Test Registration

1. Go to: `http://localhost/sem 4th website/register.php`
2. Fill in registration details
3. Check email for OTP code
4. Enter the OTP
5. If successful, you'll see: **"✓ Account Created Successfully!"**

## 🔍 If Still Not Working

Run diagnostic tests:

### Test 1: Check Database Connection
```
http://localhost/sem%204th%20website/test-database-update.php
```

### Test 2: Check PHP Configuration
```
http://localhost/sem%204th%20website/diagnose-php.php
```

### Test 3: Verify Database Setup
```
http://localhost/sem%204th%20website/setup-verify-database.php
```

## ⚙️ Manual Database Setup (If Script Doesn't Work)

If the automated script doesn't work, do this manually:

### Using Laragon Database Manager (EASIEST):

1. Open Laragon
2. Right-click on MySQL → **"MySQL Console"**
3. Copy and paste this command:

```sql
CREATE DATABASE IF NOT EXISTS cryptohub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cryptohub;
```

Then go back and run the `fix-database.php` script again.

### Using Laragon HeidiSQL (GUI TOOL):

1. Open Laragon
2. Right-click on MySQL → **"HeidiSQL"** or **"PhpMyAdmin"**
3. Create a new database:
   - Name: `cryptohub`
   - Charset: `utf8mb4`
   - Collation: `utf8mb4_unicode_ci`

Then run the `fix-database.php` script again.

## 🐛 Common Issues & Solutions

### Problem: "Cannot connect to MySQL"
- **Solution:** Make sure MySQL is running in Laragon
- **Check:** MySQL icon in Laragon should show "Running"

### Problem: "Unknown database 'cryptohub'"
- **Solution:** Run the `fix-database.php` script
- **Or:** Manually create the database using HeidiSQL

### Problem: "Table does not exist"
- **Solution:** Run the `fix-database.php` script
- This automatically creates all tables

### Problem: Still using JSON files
- **Check:** Go to `diagnose-php.php`
- Look for: `use_db_enabled(): ` should be `TRUE`
- If FALSE, check the "Debug use_db_enabled() Checks" section

## 📊 Database Structure

After setup, you should have these tables:
- `ch_users` - User accounts
- `ch_admins` - Admin accounts
- `ch_transactions` - Transactions history
- `ch_investments` - Investment records
- `ch_messages` - Messages
- `ch_cryptocurrencies` - Crypto data (12 coins)
- `ch_otps` - One-Time Passwords for verification

## ✨ What's Fixed

The OTP system has been updated to use **MySQL server time exclusively** for:
- Generating OTP expiration timestamps
- Verifying OTP validity
- Database operations

This ensures **zero timezone conflicts** between PHP and MySQL.

## 🎯 Final Verification

After setting up, verify with:

1. **Register Test**: Create an account, verify with OTP
2. **Check Database**: Go to `test-database-update.php`
3. **Look for confirmation**: "✓ INSERT successful", "✓ SELECT successful", "✓ UPDATE successful"

## 💬 Need Help?

If none of these work:
1. Check the error messages in these test files carefully
2. Make sure MySQL is actually running
3. Check that Laragon is fully started
4. Try restarting Laragon completely

---

**Start with:** `http://localhost/sem%204th%20website/fix-database.php`

This is your one-click solution!
