# CryptoHub - Real-Time Database Integration Guide

## 📋 Overview

Your CryptoHub website has been updated to use **MySQL database for real-time updates** across all cryptocurrency, investment, and transaction data. No more JSON-based storage for critical data!

## ✨ What's New

### Real-Time Data Features
- ✅ **Live Cryptocurrency Prices** - All cryptocurrencies stored in database
- ✅ **Real-Time Balance Updates** - Instant balance changes on deposit  
- ✅ **Live Investment Tracking** - All investments updated instantly
- ✅ **Real-Time Transaction History** - Every transaction logged immediately
- ✅ **Instant Withdrawals** - Deduct balances immediately upon transaction

### Technology Stack
- **Database**: MySQL with ch_cryptocurrencies, ch_investments, ch_transactions tables
- **ORM**: PDO with prepared statements (SQL injection safe)
- **Fallback**: JSON storage available if database unavailable
- **Caching**: Crypto prices stored in database with regular update capability

## 🚀 Quick Start

### Step 1: Run Database Setup

Open your browser and navigate to:
```
http://localhost/sem%204th%20website/setup_database.php
```

This will:
- ✓ Create all required MySQL tables
- ✓ Seed 12 cryptocurrencies with real market data
- ✓ Verify database connectivity
- ✓ Test helper functions

### Step 2: Verify Setup

You should see:
```
✓ SETUP COMPLETE!
Your CryptoHub website is now configured for:
✓ Real-time cryptocurrency data
✓ Real-time balance updates
✓ Real-time investment tracking
✓ Real-time transaction history
```

### Step 3: Test the Features

1. **Create an Account**: `/register.php` (email verification via OTP)
2. **Add Money**: `/add-money.php` (deposits increase balance instantly)
3. **Make Investment**: `/invest.php` (deducts from balance, creates investment record)
4. **View Dashboard**: `/dashboard.php` (shows real-time stats)

## 📊 Database Schema

### ch_cryptocurrencies
```sql
CREATE TABLE ch_cryptocurrencies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  symbol VARCHAR(20) NOT NULL UNIQUE,          -- BTC, ETH, etc.
  name VARCHAR(100) NOT NULL,                  -- Bitcoin, Ethereum, etc.
  current_price DECIMAL(32,8) NOT NULL,        -- Real-time price
  market_cap DECIMAL(48,8) DEFAULT NULL,       -- Market capitalization
  change_24h DECIMAL(10,2) DEFAULT 0,         -- 24-hour change %
  change_7d DECIMAL(10,2) DEFAULT 0,          -- 7-day change %
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  status VARCHAR(50) DEFAULT 'active'
);
```

### ch_users (Updated)
```sql
CREATE TABLE ch_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  full_name VARCHAR(255) NOT NULL,
  phone VARCHAR(50) DEFAULT NULL,
  password VARCHAR(255) NOT NULL,
  balance DECIMAL(32,8) NOT NULL DEFAULT 0.00,  -- Real-time balance
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  email_verified TINYINT(1) NOT NULL DEFAULT 0,
  last_login DATETIME DEFAULT NULL
);
```

### ch_transactions (Deposits/Withdrawals)
```sql
CREATE TABLE ch_transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tx_id VARCHAR(100) NOT NULL UNIQUE,
  user_email VARCHAR(255) NOT NULL,
  type VARCHAR(50) NOT NULL,                    -- 'deposit', 'withdrawal', 'investment'
  amount DECIMAL(32,8) NOT NULL,
  currency VARCHAR(20) NOT NULL,                -- 'USD', 'BTC', 'ETH', etc.
  from_to VARCHAR(255) DEFAULT NULL,            -- Payment method details
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status VARCHAR(50) DEFAULT 'pending'          -- 'completed', 'pending', 'failed'
);
```

### ch_investments (Crypto Holdings)
```sql
CREATE TABLE ch_investments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  inv_id VARCHAR(100) NOT NULL UNIQUE,
  user_email VARCHAR(255) NOT NULL,
  cryptocurrency VARCHAR(50) NOT NULL,          -- 'BTC', 'ETH', etc.
  amount_invested DECIMAL(32,8) NOT NULL,      -- USD invested
  coins_purchased DECIMAL(32,8) NOT NULL,      -- Amount of coins
  price_at_purchase DECIMAL(32,8) NOT NULL,    -- Lock-in price
  investment_type VARCHAR(50) NOT NULL,         -- 'spot', 'future', 'staking'
  status VARCHAR(50) DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  maturity_date DATETIME DEFAULT NULL,          -- For fixed/future trades
  FOREIGN KEY (user_email) REFERENCES ch_users(email)
);
```

## 🔧 Helper Functions (db_helpers.php)

All pages now use these database helper functions for consistency:

### Cryptocurrency Functions
```php
// Get all active cryptocurrencies
getAllCryptocurrencies()  // Returns array of cryptos

// Get single cryptocurrency by symbol
getCryptocurrencyBySymbol('BTC')  // Returns crypto data

// Get user's balance
getUserBalance($email)  // Returns float

// Get user's total invested amount
getUserTotalInvestments($email)  // Returns float

// Get user's active investments
getUserInvestments($email)  // Returns array of investments

// Get user's transactions
getUserTransactions($email, $limit = 10)  // Returns array
```

### Transaction Functions
```php
// Add a new transaction
addTransaction($userEmail, $type, $amount, $currency, $txId = null)  // Returns bool

// Update user balance
updateUserBalance($email, $newBalance)  // Returns bool

// Add investment
addInvestment($userEmail, $crypto, $amount, $coins, $price, $type)  // Returns bool
```

## 📄 Updated Pages

### dashboard.php
- **Now Displays**: Real-time balance, investments, and transactions from database
- **Data Source**: ch_users.balance, ch_investments, ch_transactions
- **Updates**: Refreshes on page load

### invest.php
- **Available Cryptos**: Fetched from ch_cryptocurrencies table
- **User Investments**: Queried from ch_investments in real-time
- **Balance Check**: Verifies against ch_users.balance
- **Investment Creation**: Inserts to ch_investments and deducts from balance

### add-money.php
- **Balance Display**: Real-time from ch_users.balance
- **Transaction Recording**: Logs to ch_transactions
- **Balance Update**: Immediately adds deposit to user's balance
- **Recent History**: Shows 5 latest transactions from database

## 🔄 Real-Time Data Flow

### When User Deposits Money:
```
1. POST /add-money.php with payment details
2. Validate amount ($10-$100,000)
3. INSERT transaction record in ch_transactions
4. UPDATE ch_users SET balance = balance + amount
5. Redirect to dashboard
6. Dashboard shows new balance immediately
```

### When User Invests in Crypto:
```
1. POST /invest.php with crypto symbol and amount
2. Check ch_cryptocurrencies for current price
3. Verify ch_users.balance is sufficient
4. INSERT investment in ch_investments
5. UPDATE ch_users SET balance = balance - amount
6. INSERT transaction record in ch_transactions
7. Dashboard shows updated balance and new investment
```

### When Dashboard Loads:
```
1. GET /dashboard.php
2. Query user's balance from ch_users
3. Query total invested from ch_investments
4. Query recent transactions from ch_transactions
5. Calculate ROI and profit/loss
6. Display all real-time data
```

## 📱 Available Cryptocurrencies

The system is pre-seeded with these cryptocurrencies:

| Symbol | Name | Status |
|--------|------|--------|
| BTC | Bitcoin | ✓ Active |
| ETH | Ethereum | ✓ Active |
| BNB | Binance Coin | ✓ Active |
| XRP | Ripple | ✓ Active |
| SOL | Solana | ✓ Active |
| ADA | Cardano | ✓ Active |
| DOGE | Dogecoin | ✓ Active |
| USDT | Tether | ✓ Active |
| USDC | USD Coin | ✓ Active |
| LINK | Chainlink | ✓ Active |
| LTC | Litecoin | ✓ Active |
| XLM | Stellar Lumens | ✓ Active |

To add more cryptocurrencies, insert directly into database:
```sql
INSERT INTO ch_cryptocurrencies (symbol, name, current_price, change_24h, change_7d, status)
VALUES ('SOL', 'Solana', 165.00, 2.9, 7.4, 'active');
```

## 🔐 Security Features

- **SQL Injection Protection**: PDO prepared statements used everywhere
- **Password Hashing**: Uses PASSWORD_DEFAULT (currently bcrypt)
- **Email Verification**: OTP-based email verification for registration
- **OTP for Sensitive Operations**: Password changes and resets require OTP
- **Session Management**: Server-side session validation on all protected pages
- **No Sensitive Data Storage**: Card numbers not stored, only last 4 digits

## 🔄 Updating Cryptocurrency Prices

Prices can be updated via SQL or cronjob:

### Manual Update:
```sql
UPDATE ch_cryptocurrencies SET current_price = 45000, change_24h = 3.5 WHERE symbol = 'BTC';
```

### Automated Update (create update_prices.php):
You can create a cronjob to fetch live prices from CoinGecko API and update the database every hour.

## 🐛 Troubleshooting

### Pages Show $0.00 Balance:
- Run `/setup_database.php` again to verify tables exist
- Check ch_users table: `SELECT * FROM ch_users;`
- Ensure user email matches session email

### Investments Not Showing:
- Check ch_investments table: `SELECT * FROM ch_investments;`
- Verify investment_type is in ('spot', 'future', 'staking')
- Check status = 'active'

### Transactions Not Recording:
- Verify ch_transactions table exists
- Check ch_transaction type field: should be 'deposit', 'withdrawal', or 'investment'
- Ensure transaction is being added: check type and currency fields

### Database Connection Issues:
- Verify MySQL is running: `services.msc` → MySQL
- Check config.php credentials match your MySQL setup
- Ensure 'cryptohub' database exists
- Grant proper permissions: `GRANT ALL ON cryptohub.* TO 'root'@'localhost';`

## ✅ Verification Checklist

After setup, verify these work:

- [ ] `/setup_database.php` runs without errors
- [ ] Can register new account at `/register.php`
- [ ] Can log in and see admin panel
- [ ] Dashboard shows real balance (even if $0.00)
- [ ] Can add money and balance updates instantly
- [ ] Can invest and balance decreases immediately
- [ ] Recent transactions appear on dashboard
- [ ] All cryptocurrency prices display correctly
- [ ] Investment history shows on dashboard

## 📞 Support

If you encounter issues:
1. Check error logs: Look at your MySQL error log
2. Test database: Run a simple query in PhpMyAdmin
3. Clear browser cache: Old JS/data might be cached
4. Restart MySQL/Apache if tables not created

## 🎯 Future Enhancements

Possible additions for real-time updates:
- WebSocket for price updates without page refresh
- Cronjob to update crypto prices from API every hour
- Real-time profit/loss calculation based on current prices
- Email notifications for large price movements
- Automated staking rewards calculation

---

**Last Updated**: April 2026
**Database Version**: 1.0
**All data is now stored in MySQL database with real-time updates! 🚀**
