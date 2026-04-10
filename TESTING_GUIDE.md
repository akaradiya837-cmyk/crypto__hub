# 🧪 CryptoHub Real-Time Database Testing Guide

## Start Here: 5-Minute Quick Test

### What You'll Do:
1. Create a test account
2. Add $500 to account
3. Invest $200 in Bitcoin
4. View real-time updates on dashboard

---

## TEST 1: Register & Create Account

**URL:** `http://localhost/sem%204th%20website/register.php`

### Steps:
1. Fill in registration form:
   - **Email**: `testuser@example.com`
   - **Full Name**: `Test User`
   - **Phone**: `1234567890`
   - **Password**: `TestPass@123`

2. Click **"Register"**
   - Should show: "OTP verification code sent to testuser@example.com"

3. Check your email:
   - If using Gmail: Check spam folder
   - Look for email from: `cryptoadminpirouser@gmail.com`
   - Subject: "Email Verification - CryptoHub"
   - Find the **6-digit code**

4. Enter OTP code:
   - Paste the 6-digit code from email
   - Click **"Verify Code"**

5. You should see:
   ```
   ✓ Email verified successfully!
   You can now log in with your account.
   ```

### Expected Database Result:
```sql
SELECT * FROM ch_users WHERE email = 'testuser@example.com';
```
Should return:
- `balance: 0.00` (new account)
- `email_verified: 1` (verified)

---

## TEST 2: Add Money to Account

**URL:** `http://localhost/sem%204th%20website/add-money.php`

### Current Status:
- **Account Balance**: $0.00

### Steps:
1. Login first:
   - Go to `/index.php` → Login
   - Email: `testuser@example.com`
   - Password: `TestPass@123`

2. Navigate to: `/add-money.php`

3. Fill in the form:
   - **Amount**: `500` (for $500)
   - **Payment Method**: Select "Credit Card"

4. If Credit Card selected:
   - **Card Name**: `Test Card`
   - **Card Number**: `1234 5678 9012 3456`
   - **Expiry**: `12/25`
   - **CVV**: `123`

5. Check the box: "I confirm the information is correct"

6. Click **"Add Money"**

7. You should see:
   ```
   ✓ Transaction completed successfully!
   Your account has been credited.
   ```

### Expected Result - Database:
```sql
-- User balance updated
SELECT balance FROM ch_users WHERE email = 'testuser@example.com';
-- Result: 500.00

-- Transaction recorded
SELECT * FROM ch_transactions WHERE user_email = 'testuser@example.com';
-- Result: type='deposit', amount=500, status='completed'
```

### Visual Confirmation:
The page should show: **Current Balance: $500.00**

---

## TEST 3: Make an Investment

**URL:** `http://localhost/sem%204th%20website/invest.php`

### Current Status:
- **Available Balance**: $500.00
- **Total Investment**: $0.00

### Steps:
1. Navigate to: `/invest.php`

2. You should see the form with cryptocurrencies loaded:
   - Bitcoin (BTC) - $42,500.00
   - Ethereum (ETH) - $2,850.00
   - Ripple (XRP) - $0.52
   - And 9 others...

3. Fill in investment form:
   - **Select Cryptocurrency**: `Bitcoin (BTC) - $42,500.00`
   - **Investment Amount**: `200` (for $200)
   - **Investment Type**: `Spot Trading (Immediate)`

4. You should see auto-calculated:
   - **Current Price**: $42,500.00
   - **Amount of Coins**: 0.00470588 BTC

5. Check the box: "I confirm I want to invest in this cryptocurrency"

6. Click **"Invest Now"**

7. You should see:
   ```
   ✓ Successfully invested $200.00 in Bitcoin.
   You purchased 0.00470588 coins.
   ```

### Expected Result - Database:

```sql
-- User balance decreased by $200
SELECT balance FROM ch_users WHERE email = 'testuser@example.com';
-- Result: 300.00 (was 500.00, now reduced by 200)

-- Investment recorded
SELECT * FROM ch_investments WHERE user_email = 'testuser@example.com';
-- Result: 
--   cryptocurrency='BTC'
--   amount_invested=200
--   coins_purchased=0.00470588
--   price_at_purchase=42500
--   investment_type='spot'

-- Transaction recorded
SELECT * FROM ch_transactions WHERE user_email = 'testuser@example.com' ORDER BY created_at DESC LIMIT 5;
-- Result: Shows deposit AND investment transaction
```

### Visual Confirmation:
- Available Balance: **$300.00** ✓
- Total Investment: **$200.00** ✓

---

## TEST 4: Verify Real-Time Updates on Dashboard

**URL:** `http://localhost/sem%204th%20website/dashboard.php`

### What You Should See:

#### Portfolio Summary Cards (Top):
```
┌─────────────────────────────────────┐
│ Total Balance         │    $300.00   │
│ Total Investment      │    $200.00   │
│ Total Profit/Loss     │     +$0.00   │
│ ROI                   │     0.00%    │
└─────────────────────────────────────┘
```

#### Recent Transactions Section:
```
┌──────────────────────────────────────┐
│ Type          │ Amount   │ Date      │
├──────────────────────────────────────┤
│ Investment    │  -$200   │ 2024-04-02
│ Deposit       │  +$500   │ 2024-04-02
└──────────────────────────────────────┘
```

#### Your Crypto Holdings Section:
```
┌────────────────────────────────────────┐
│ Bitcoin (BTC)                          │
│ Coins: 0.00470588                      │
│ Invested: $200.00                      │
└────────────────────────────────────────┘
```

### Database Verification:
All data shown is **REAL-TIME** from database:
- Balance from: `ch_users.balance`
- Investments from: `ch_investments`
- Transactions from: `ch_transactions`

---

## TEST 5: Verify Database Integrity

### Open Database Tool (HeidiSQL is in Laragon):
**Path**: `C:\laragon\bin\heidisql\heidisql.exe`

Or use command line:
```
mysql -u root -p cryptohub
```

### Run These Verifications:

**1. Check User Balance Updated:**
```sql
SELECT email, full_name, balance FROM ch_users WHERE email = 'testuser@example.com';
```
Expected: `testuser@example.com | Test User | 300.00`

**2. Check Cryptocurrencies Seeded:**
```sql
SELECT symbol, name, current_price FROM ch_cryptocurrencies LIMIT 5;
```
Expected: 12 rows with BTC, ETH, BNB, etc.

**3. Check Transactions:**
```sql
SELECT user_email, type, amount, currency, status FROM ch_transactions ORDER BY created_at DESC LIMIT 10;
```
Expected: 
- One `deposit` row with amount=500
- One `investment` row with amount=200

**4. Check Investments:**
```sql
SELECT cryptocurrency, amount_invested, coins_purchased, status FROM ch_investments WHERE user_email = 'testuser@example.com';
```
Expected:
- BTC, 200, 0.00470588, active

---

## TEST 6: Test Profile Password Change with OTP

**URL:** `http://localhost/sem%204th%20website/profile.php`

### Steps:
1. Click on the **Change Password** section

2. Fill in:
   - **Current Password**: `TestPass@123`
   - **New Password**: `NewPass@123`

3. Click **"Change Password"**

4. You should see:
   ```
   A 6-digit verification code has been sent to testuser@example.com
   ```

5. Check your email for the OTP code

6. Enter the 6-digit code

7. Click **"Verify Code"**

8. You should see:
   ```
   ✓ Password changed successfully!
   ```

### Expected Database Result:
Password hash in `ch_users` updated for your email

---

## TEST 7: Test Forgot Password Flow

**URL:** `http://localhost/sem%204th%20website/forgot-password.php`

### Steps:
1. Enter email: `testuser@example.com`

2. Click **"Send Verification Code"**

3. Check email for OTP

4. Enter 6-digit code

5. Enter new password: `AnotherPass@123`

6. Click **"Reset Password"**

7. Try logging in with new password

---

## ✅ All Tests Verification Checklist

- [ ] Account created with OTP verification working
- [ ] Deposit of $500 added successfully
- [ ] Balance updated to $500.00 instantly
- [ ] Can select and view cryptocurrencies
- [ ] Investment of $200 in Bitcoin completed
- [ ] Balance updated to $300.00 instantly
- [ ] Investment appears on dashboard
- [ ] Dashboard shows real-time data
- [ ] Database shows correct balances
- [ ] After investment: $300 balance, $200 invested
- [ ] Password change requires OTP
- [ ] Forgot password uses OTP flow
- [ ] All transactions logged in database

---

## 🔍 Troubleshooting Tests

### Issue: Balance shows $0.00 after deposit

**Solution:**
1. Refresh page (F5)
2. Log out and log back in
3. Check database:
   ```sql
   SELECT balance FROM ch_users WHERE email = 'testuser@example.com';
   ```

### Issue: Cryptocurrency list empty on invest.php

**Solution:**
1. Run setup again: `/setup_database.php`
2. Check table:
   ```sql
   SELECT COUNT(*) FROM ch_cryptocurrencies;
   ```
   Should return: 12

### Issue: Investment amount doesn't decrease balance

**Solution:**
1. Refresh page
2. Make sure balance is sufficient
3. Check database:
   ```sql
   SELECT balance FROM ch_users WHERE email = 'testuser@example.com';
   ```

### Issue: Email not receiving OTP codes

**Solution:**
1. Check spam folder in email
2. Verify SMTP is working: Check email address in `otp_mailer.php`
3. Look for error logs in browser console (F12 → Console tab)

---

## 📊 Expected Database State After All Tests

### ch_users Table:
```
email: testuser@example.com
balance: 300.00                (500 - 200)
email_verified: 1              (verified via OTP)
password: [HASHED]             (changed via OTP)
```

### ch_cryptocurrencies Table:
```
12 rows with BTC, ETH, BNB, XRP, SOL, ADA, DOGE, USDT, USDC, LINK, LTC, XLM
Each with current_price, change_24h, change_7d
```

### ch_transactions Table:
```
Row 1: user_email, type=deposit, amount=500, currency=USD, status=completed
Row 2: user_email, type=investment, amount=200, currency=BTC, status=completed
```

### ch_investments Table:
```
Row 1: cryptocurrency=BTC, amount_invested=200, coins_purchased=0.00470588, status=active
```

---

## 🎯 FAQs During Testing

**Q: Why does balance update after page refresh?**  
A: The page loads the current balance from the database. Refresh to see latest values.

**Q: Can I test with multiple users?**  
A: Yes! Register multiple accounts and they'll all have independent balances.

**Q: What happens if I invest more than balance?**  
A: Should show error: "Insufficient balance. Your current balance is $X"

**Q: Can I undo investments?**  
A: Not in current version. This would be a withdrawal feature.

**Q: How long do OTP codes last?**  
A: 10 minutes. After that, you'll need to request a new code.

---

## 🚀 Success! You Now Have:

✅ Real-time cryptocurrency tracking  
✅ Real-time balance updates  
✅ Real-time investment tracking  
✅ Real-time transaction history  
✅ OTP email verification  
✅ Secure password management  

**All data stored in MySQL database with instant updates!** 🎉

---

**Last Updated**: April 2026  
**Status**: Ready for Testing ✓
