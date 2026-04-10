# CryptoHub Database Setup Guide

This guide will help you set up the MySQL database and migrate all data from JSON files to the database.

## Prerequisites

- **MySQL Server**: Running and accessible (default: localhost:3306)
- **PHP with PDO MySQL extension**: For running migration scripts
- **Laragon** or similar PHP environment

## Configuration

Database configuration is defined in `config.php`:

```php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'cryptohub');
define('DB_USER', 'root');
define('DB_PASS', '');      // Empty password for Laragon's default MySQL
define('DB_PORT', '3306');
```

Update these values if your MySQL credentials differ.

## Step-by-Step Setup

### 1. Create the Database

Open MySQL command line or MySQL Workbench and run:

```sql
CREATE DATABASE cryptohub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2. Import the Schema

Run the schema file to create all tables:

```bash
mysql -u root -p cryptohub < schema.sql
```

For Laragon (if password is empty):
```bash
mysql -u root cryptohub < schema.sql
```

Or manually import via:
- MySQL Workbench: File → Open SQL Script → Select `schema.sql` → Execute
- PhpMyAdmin: Import → Select `schema.sql` → Go

**Verify**: Check that these tables were created:
- `ch_users`
- `ch_admins`
- `ch_transactions`
- `ch_investments`
- `ch_messages`

### 3. Run the Migration Script

Navigate to the website folder and run:

```bash
cd c:\laragon\www\sem\ 4th\ website
php migrate_json_to_mysql.php
```

**Expected Output**:
```
Database connection successful.
Importing users...
Imported X users.
Importing admins...
Imported X admins.
Importing transactions...
Imported X transactions.
Importing investments...
Imported X investments.
Importing contact messages...
Imported X messages.
Migration complete.
```

### 4. Verify Data Migration

Check your database in PhpMyAdmin or MySQL Workbench:

```sql
SELECT COUNT(*) FROM ch_users;
SELECT COUNT(*) FROM ch_transactions;
SELECT COUNT(*) FROM ch_investments;
SELECT COUNT(*) FROM ch_messages;
```

### 5. Test the Website

- Try **registering** a new account → Check `ch_users` table
- Try **adding money** → Check `ch_transactions` and `ch_users.balance`
- Try **investing** → Check `ch_investments` table
- Try **contact form** → Check `ch_messages` table
- Try **changing profile/password** → Check updates in `ch_users`

## Features

The website now uses **database-first** approach with JSON fallback:

1. **MySQL as Primary Storage**: All new data is saved to database first
2. **JSON Fallback**: If database is unavailable, data is saved to JSON files
3. **User Balance Tracking**: `ch_users.balance` holds current wallet balance
4. **Transaction History**: All money in/out transactions tracked in `ch_transactions`
5. **Investment Portfolio**: User investments tracked in `ch_investments`
6. **Contact Messages**: User messages from contact form in `ch_messages`

## Tables Overview

### ch_users
- Stores user accounts
- Fields: email, full_name, phone, password, balance, created_at, email_verified, last_login

### ch_admins  
- Stores admin accounts
- Fields: email, full_name, phone, password, created_at, status

### ch_transactions
- Stores all add-money transactions
- Fields: tx_id, user_email, type, amount, currency, from_to, created_at, status

### ch_investments
- Stores all investment records
- Fields: inv_id, user_email, cryptocurrency, amount_invested, coins_purchased, price_at_purchase, investment_type, status, created_at, maturity_date

### ch_messages
- Stores contact form submissions
- Fields: id, user_email, user_name, subject, message, ip_address, status, created_at

## Troubleshooting

### "Database connection unavailable"
- Verify MySQL is running
- Check `config.php` credentials
- Ensure `cryptohub` database exists

### "Table doesn't exist"
- Verify all tables were created: `SHOW TABLES;` in MySQL
- Re-import schema.sql if needed

### PDO Extension Not Loaded
- Add `extension=pdo_mysql` to `php.ini`
- Restart PHP/Apache

### Migration Fails
- Check MySQL user has CREATE/INSERT/UPDATE permissions
- Verify JSON files exist before migration
- Check PHP error logs for detailed error messages

## Reverting to JSON-Only (if needed)

To disable database and use JSON fallback only, add to `config.php`:

```php
define('FORCE_JSON_STORAGE', true);
```

## Next Steps

1. Complete the setup above
2. Test each feature thoroughly
3. Monitor database for any errors
4. Gradually retire JSON files once confident in database stability

---

**Last Updated**: April 2, 2026  
**Database Support**: MySQL 5.7+
