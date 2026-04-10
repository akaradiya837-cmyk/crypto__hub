-- Schema for CryptoHub minimal tables
-- Create database (run as a separate step if DB does not exist):
-- CREATE DATABASE cryptohub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ch_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  full_name VARCHAR(255) NOT NULL,
  phone VARCHAR(50) DEFAULT NULL,
  password VARCHAR(255) NOT NULL,
  balance DECIMAL(32,8) NOT NULL DEFAULT 0.00,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  email_verified TINYINT(1) NOT NULL DEFAULT 0,
  last_login DATETIME DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS ch_transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tx_id VARCHAR(100) NOT NULL UNIQUE,
  user_email VARCHAR(255) NOT NULL,
  type VARCHAR(50) NOT NULL,
  amount DECIMAL(32,8) NOT NULL,
  currency VARCHAR(20) NOT NULL,
  from_to VARCHAR(255) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status VARCHAR(50) DEFAULT 'pending'
);

CREATE TABLE IF NOT EXISTS ch_investments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  inv_id VARCHAR(100) NOT NULL UNIQUE,
  user_email VARCHAR(255) NOT NULL,
  cryptocurrency VARCHAR(50) NOT NULL,
  amount_invested DECIMAL(32,8) NOT NULL,
  coins_purchased DECIMAL(32,8) NOT NULL,
  price_at_purchase DECIMAL(32,8) NOT NULL,
  investment_type VARCHAR(50) NOT NULL,
  status VARCHAR(50) DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  maturity_date DATETIME DEFAULT NULL,
  FOREIGN KEY (user_email) REFERENCES ch_users(email)
);

CREATE TABLE IF NOT EXISTS ch_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_email VARCHAR(255) NOT NULL,
  user_name VARCHAR(255) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  ip_address VARCHAR(45) DEFAULT NULL,
  status VARCHAR(50) DEFAULT 'unread',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_email) REFERENCES ch_users(email)
);

CREATE TABLE IF NOT EXISTS ch_cryptocurrencies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  symbol VARCHAR(20) NOT NULL UNIQUE,
  name VARCHAR(100) NOT NULL,
  current_price DECIMAL(32,8) NOT NULL DEFAULT 0,
  market_cap DECIMAL(48,8) DEFAULT NULL,
  change_24h DECIMAL(10,2) DEFAULT 0,
  change_7d DECIMAL(10,2) DEFAULT 0,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  status VARCHAR(50) DEFAULT 'active'
);

CREATE TABLE IF NOT EXISTS ch_otps (
  id INT AUTO_INCREMENT PRIMARY KEY,
  target VARCHAR(255) NOT NULL,
  otp_code VARCHAR(20) NOT NULL,
  purpose VARCHAR(100) DEFAULT 'verification',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at DATETIME NOT NULL
);

-- Indexes
-- Note: some MySQL versions don't support IF NOT EXISTS on CREATE INDEX
CREATE INDEX idx_ch_transactions_user_email ON ch_transactions (user_email);
CREATE INDEX idx_ch_investments_user_email ON ch_investments (user_email);
CREATE INDEX idx_ch_messages_user_email ON ch_messages (user_email);
CREATE INDEX idx_ch_messages_status ON ch_messages (status);
CREATE INDEX idx_ch_otps_target ON ch_otps (target);
CREATE INDEX idx_ch_otps_expires_at ON ch_otps (expires_at);
