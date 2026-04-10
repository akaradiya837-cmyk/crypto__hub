// ===== Form Validation =====

// Email validation
function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Phone validation
function validatePhone(phone) {
    const phoneRegex = /^\d{10,}$/;
    return phoneRegex.test(phone.replace(/\D/g, ''));
}

// Password validation
function validatePassword(password) {
    return password && password.length >= 8;
}

// ===== Password Strength Checker =====
function getPasswordStrength(password) {
    let strength = 0;
    if (!password) return { score: 0, level: 'Weak', color: '#d32f2f', message: 'Password is required' };
    
    // Length check
    if (password.length >= 8) strength++;
    if (password.length >= 12) strength++;
    if (password.length >= 16) strength++;
    
    // Has lowercase
    if (/[a-z]/.test(password)) strength++;
    
    // Has uppercase
    if (/[A-Z]/.test(password)) strength++;
    
    // Has numbers
    if (/\d/.test(password)) strength++;
    
    // Has special characters
    if (/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) strength++;
    
    // Determine level
    let level, color, message;
    if (strength < 2) {
        level = 'Weak';
        color = '#d32f2f';
        message = 'Add uppercase, numbers, and special characters';
    } else if (strength < 4) {
        level = 'Fair';
        color = '#f57c00';
        message = 'Good, but add more variety';
    } else if (strength < 6) {
        level = 'Good';
        color = '#fbc02d';
        message = 'Pretty good!';
    } else {
        level = 'Strong';
        color = '#388e3c';
        message = 'Excellent password!';
    }
    
    return { score: strength, level, color, message };
}

// Update password strength display
function updatePasswordStrength(inputId, displayId) {
    const passwordInput = document.getElementById(inputId);
    const strengthDisplay = document.getElementById(displayId);
    
    if (!passwordInput || !strengthDisplay) return;
    
    passwordInput.addEventListener('input', () => {
        const password = passwordInput.value;
        const strength = getPasswordStrength(password);
        
        strengthDisplay.innerHTML = `
            <div class="password-strength-container">
                <div class="password-strength-bar">
                    <div class="password-strength-fill" style="width: ${(strength.score / 7) * 100}%; background-color: ${strength.color};"></div>
                </div>
                <span class="password-strength-text" style="color: ${strength.color};">
                    <strong>${strength.level}</strong> - ${strength.message}
                </span>
            </div>
        `;
    });
}

// Initialize password strength checkers
document.addEventListener('DOMContentLoaded', () => {
    // Registration page
    updatePasswordStrength('regPassword', 'regPasswordStrength');
    
    // Profile password change
    updatePasswordStrength('newPassword', 'newPasswordStrength');
    
    // Forgot password reset
    updatePasswordStrength('resetNewPassword', 'resetNewPasswordStrength');
    
    // Admin password change
    updatePasswordStrength('adminNewPassword', 'adminNewPasswordStrength');
});

// Card number validation (format check only for testing)
// Note: In production, also validate with Luhn algorithm
function validateCardNumber(cardNumber) {
    const cardNum = cardNumber.replace(/\s/g, '');
    // Accept any 13-19 digit number for testing
    // Production should also verify with Luhn algorithm
    return /^\d{13,19}$/.test(cardNum);
}

// Expiry date validation
function validateExpiry(expiry) {
    const expiryRegex = /^\d{2}\/\d{2}$/;
    if (!expiryRegex.test(expiry)) return false;
    
    const [month, year] = expiry.split('/');
    const monthNum = parseInt(month, 10);
    
    // For testing, just validate month is 1-12
    // In production, also check if card hasn't expired
    return monthNum >= 1 && monthNum <= 12;
}

// CVV validation
function validateCVV(cvv) {
    return /^\d{3,4}$/.test(cvv);
}

// ===== Login Form Validation =====
const loginForm = document.getElementById('loginForm');
if (loginForm) {
    loginForm.addEventListener('submit', (e) => {
        // ONLY prevent the form from submitting if validation fails
        if (!validateLoginForm()) {
            e.preventDefault();
            return; 
        }

        // If validation passes, set up the frontend data
        const email = document.getElementById('loginEmail').value.trim();
        const userName = email ? email.split('@')[0] : 'User';

        try {
            localStorage.setItem('loggedInUser', email);
            localStorage.setItem('currentUser', userName);
            localStorage.setItem('lastLogin', new Date().toLocaleString());

            // Initialize balances if missing
            if (!localStorage.getItem('userBalance')) localStorage.setItem('userBalance', '1000');
            if (!localStorage.getItem('userInvestment')) localStorage.setItem('userInvestment', '0');
        } catch (err) {
            console.warn('Unable to access localStorage:', err);
        }
        
        // The form will now naturally submit to index.php to process the login
    });
}

function validateLoginForm() {
    let isValid = true;
    
    // Email validation - BUT ALLOW "admin" as a shortcut
    const email = document.getElementById('loginEmail');
    const emailError = document.getElementById('loginEmailError');
    const emailValue = email.value.trim().toLowerCase();
    
    // Special case: allow "admin" without email validation
    if (emailValue === 'admin') {
        emailError.textContent = '';
    } else if (!validateEmail(email.value)) {
        emailError.textContent = 'Please enter a valid email address';
        isValid = false;
    } else {
        emailError.textContent = '';
    }
    
    // Password validation
    const password = document.getElementById('loginPassword');
    const passwordError = document.getElementById('loginPasswordError');
    if (!password.value) {
        passwordError.textContent = 'Password is required';
        isValid = false;
    } else {
        passwordError.textContent = '';
    }
    
    return isValid;
}

// ===== Register Form Validation =====
const registerForm = document.getElementById('registerForm');
if (registerForm) {
    registerForm.addEventListener('submit', (e) => {
        // ONLY prevent the form from submitting if validation fails
        if (!validateRegisterForm()) {
            e.preventDefault();
            return;
        }

        // Setup localStorage for the frontend UI
        const fullName = document.getElementById('regFullName').value.trim();
        const email = document.getElementById('regEmail').value.trim();

        try {
            localStorage.setItem('loggedInUser', email);
            localStorage.setItem('currentUser', fullName);
            localStorage.setItem('lastLogin', new Date().toLocaleString());
            // Give new users a starter balance
            localStorage.setItem('userBalance', '500');
            localStorage.setItem('userInvestment', '0');
        } catch (err) {
            console.warn('Unable to access localStorage:', err);
        }

        // The form will now naturally submit to register.php to save the user
    });
}

function validateRegisterForm() {
    let isValid = true;
    
    // Full Name validation
    const fullName = document.getElementById('regFullName');
    const fullNameError = document.getElementById('regFullNameError');
    if (fullName.value.trim().split(' ').length < 2) {
        fullNameError.textContent = 'Please enter your full name (first and last)';
        isValid = false;
    } else {
        fullNameError.textContent = '';
    }
    
    // Email validation
    const email = document.getElementById('regEmail');
    const emailError = document.getElementById('regEmailError');
    if (!validateEmail(email.value)) {
        emailError.textContent = 'Please enter a valid email address';
        isValid = false;
    } else {
        emailError.textContent = '';
    }
    
    // Phone validation
    const phone = document.getElementById('regPhone');
    const phoneError = document.getElementById('regPhoneError');
    if (!validatePhone(phone.value)) {
        phoneError.textContent = 'Please enter a valid phone number (at least 10 digits)';
        isValid = false;
    } else {
        phoneError.textContent = '';
    }
    
    // Password validation
    const password = document.getElementById('regPassword');
    const passwordError = document.getElementById('regPasswordError');
    if (!validatePassword(password.value)) {
        passwordError.textContent = 'Password must be at least 8 characters long';
        isValid = false;
    } else {
        passwordError.textContent = '';
    }
    
    // Confirm password validation
    const confirmPassword = document.getElementById('regConfirmPassword');
    const confirmPasswordError = document.getElementById('regConfirmPasswordError');
    if (confirmPassword.value !== password.value) {
        confirmPasswordError.textContent = 'Passwords do not match';
        isValid = false;
    } else {
        confirmPasswordError.textContent = '';
    }
    
    // Terms validation
    const terms = document.querySelector('#registerForm input[name="terms"]');
    const termsError = document.getElementById('regTermsError');
    if (!terms || !terms.checked) {
        termsError.textContent = 'You must agree to the terms and conditions';
        isValid = false;
    } else {
        termsError.textContent = '';
    }
    
    return isValid;
}

// ===== Add Money Form Validation =====
function validateAddMoneyForm() {
    let isValid = true;
    
    // Payment method validation
    const paymentMethod = document.getElementById('paymentMethod');
    const paymentMethodError = document.getElementById('paymentMethodError');
    if (!paymentMethod.value) {
        paymentMethodError.textContent = 'Please select a payment method';
        isValid = false;
    } else {
        paymentMethodError.textContent = '';
    }
    
    // Amount validation
    const amount = document.getElementById('amount');
    const amountError = document.getElementById('amountError');
    const amountValue = parseFloat(amount.value);
    if (isNaN(amountValue) || amountValue < 10 || amountValue > 100000) {
        amountError.textContent = 'Amount must be between $10 and $100,000';
        isValid = false;
    } else {
        amountError.textContent = '';
    }
    
    // Validate card details if payment method is card
    if (paymentMethod.value === 'credit-card' || paymentMethod.value === 'debit-card') {
        // Card name validation
        const cardName = document.getElementById('cardName');
        const cardNameError = document.getElementById('cardNameError');
        if (!cardName.value.trim()) {
            cardNameError.textContent = 'Cardholder name is required';
            isValid = false;
        } else {
            cardNameError.textContent = '';
        }
        
        // Card number validation
        const cardNumber = document.getElementById('cardNumber');
        const cardNumberError = document.getElementById('cardNumberError');
        if (!validateCardNumber(cardNumber.value)) {
            cardNumberError.textContent = 'Please enter a valid card number';
            isValid = false;
        } else {
            cardNumberError.textContent = '';
        }
        
        // Expiry validation
        const cardExpiry = document.getElementById('cardExpiry');
        const cardExpiryError = document.getElementById('cardExpiryError');
        if (!validateExpiry(cardExpiry.value)) {
            cardExpiryError.textContent = 'Please enter a valid expiry date (MM/YY)';
            isValid = false;
        } else {
            cardExpiryError.textContent = '';
        }
        
        // CVV validation
        const cardCVV = document.getElementById('cardCVV');
        const cardCVVError = document.getElementById('cardCVVError');
        if (!validateCVV(cardCVV.value)) {
            cardCVVError.textContent = 'Please enter a valid CVV (3-4 digits)';
            isValid = false;
        } else {
            cardCVVError.textContent = '';
        }
    }
    
    // Validate bank details if payment method is bank transfer
    if (paymentMethod.value === 'bank-transfer') {
        const bankName = document.getElementById('bankName');
        const bankNameError = document.getElementById('bankNameError');
        if (!bankName.value.trim()) {
            bankNameError.textContent = 'Bank name is required';
            isValid = false;
        } else {
            bankNameError.textContent = '';
        }
        
        const accountNumber = document.getElementById('accountNumber');
        const accountNumberError = document.getElementById('accountNumberError');
        if (!accountNumber.value.trim()) {
            accountNumberError.textContent = 'Account number is required';
            isValid = false;
        } else {
            accountNumberError.textContent = '';
        }
        
        const routingNumber = document.getElementById('routingNumber');
        const routingNumberError = document.getElementById('routingNumberError');
        if (!routingNumber.value.trim()) {
            routingNumberError.textContent = 'Routing number is required';
            isValid = false;
        } else {
            routingNumberError.textContent = '';
        }
    }
    
    // Confirm validation
    const confirm = document.querySelector('#addMoneyForm input[name="confirm"]');
    const confirmError = document.getElementById('confirmError');
    if (!confirm.checked) {
        confirmError.textContent = 'Please confirm the information';
        isValid = false;
    } else {
        confirmError.textContent = '';
    }
    
    return isValid;
}

// ===== Invest Form Validation =====
function validateInvestForm() {
    let isValid = true;
    
    // Selected crypto validation
    const selectedCrypto = document.getElementById('selectedCrypto');
    const selectedCryptoError = document.getElementById('selectedCryptoError');
    if (!selectedCrypto.value) {
        selectedCryptoError.textContent = 'Please select a cryptocurrency';
        isValid = false;
    } else {
        selectedCryptoError.textContent = '';
    }
    
    // Investment amount validation
    const investmentAmount = document.getElementById('investmentAmount');
    const investmentAmountError = document.getElementById('investmentAmountError');
    const amount = parseFloat(investmentAmount.value);
    if (isNaN(amount) || amount <= 0) {
        investmentAmountError.textContent = 'Please enter a valid amount';
        isValid = false;
    } else {
        investmentAmountError.textContent = '';
    }
    
    // Investment type validation
    const investmentType = document.getElementById('investmentType');
    const investmentTypeError = document.getElementById('investmentTypeError');
    if (!investmentType.value) {
        investmentTypeError.textContent = 'Please select an investment type';
        isValid = false;
    } else {
        investmentTypeError.textContent = '';
    }
    
    // Confirm validation
    const confirmInvestment = document.querySelector('#investForm input[name="confirmInvestment"]');
    const confirmInvestmentError = document.getElementById('confirmInvestmentError');
    if (!confirmInvestment.checked) {
        confirmInvestmentError.textContent = 'Please confirm your investment';
        isValid = false;
    } else {
        confirmInvestmentError.textContent = '';
    }
    
    return isValid;
}

// ===== Real-time Validation =====
// Login form real-time validation - REMOVED for admin shortcuts to work properly
// Only password validation in real-time

const loginPasswordInput = document.getElementById('loginPassword');
if (loginPasswordInput) {
    loginPasswordInput.addEventListener('blur', () => {
        const passwordError = document.getElementById('loginPasswordError');
        if (!loginPasswordInput.value) {
            passwordError.textContent = 'Password is required';
        } else {
            passwordError.textContent = '';
        }
    });
}

// ===== Theme Helpers =====
// call `applyOfficialBackground()` to switch back to the original official gradient
// call `revertToDefaultBackground()` to use the current default background
function applyOfficialBackground() {
    document.body.classList.add('official-theme');
}

function revertToDefaultBackground() {
    document.body.classList.remove('official-theme');
}

// Expose to global so you can call from console or other scripts
window.applyOfficialBackground = applyOfficialBackground;
window.revertToDefaultBackground = revertToDefaultBackground;

// Register form real-time validation
const regPasswordInput = document.getElementById('regPassword');
if (regPasswordInput) {
    regPasswordInput.addEventListener('blur', () => {
        const passwordError = document.getElementById('regPasswordError');
        if (!validatePassword(regPasswordInput.value)) {
            passwordError.textContent = 'Password must be at least 8 characters long';
        } else {
            passwordError.textContent = '';
        }
    });
}

const regConfirmPasswordInput = document.getElementById('regConfirmPassword');
if (regConfirmPasswordInput) {
    regConfirmPasswordInput.addEventListener('blur', () => {
        const confirmPasswordError = document.getElementById('regConfirmPasswordError');
        if (regConfirmPasswordInput.value !== regPasswordInput.value) {
            confirmPasswordError.textContent = 'Passwords do not match';
        } else {
            confirmPasswordError.textContent = '';
        }
    });
}

// ===== Logout Functionality =====
const logoutBtns = document.querySelectorAll('#logoutBtn');
logoutBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        // Clear client-side state but allow navigation to proceed (server logout handled by logout.php)
        try { localStorage.clear(); } catch (e) {}
    });
});

// ===== Page Load Checks =====
// Check if user is logged in on protected pages
window.addEventListener('load', () => {
    const currentPage = window.location.pathname;

    const loggedInUser = localStorage.getItem('loggedInUser') || (window.serverUser && window.serverUser.email) || null;
    
    if ((currentPage.includes('index.php') || currentPage.endsWith('/')) && loggedInUser) {
        // Optionally redirect to dashboard if already logged in
        // Uncomment the line below to enable this behavior
        // window.location.href = 'dashboard.php';
    }
});

// ===== Admin Form Validation =====

// Admin Login Form Validation
const adminLoginForm = document.getElementById('adminLoginForm');
if (adminLoginForm) {
    adminLoginForm.addEventListener('submit', (e) => {
        // Allow server-side admin login; only block if client-side validation fails
        if (!validateAdminLoginForm()) {
            e.preventDefault();
        }
    });
}

function validateAdminLoginForm() {
    let isValid = true;
    
    // Email validation
    const email = document.getElementById('adminEmail');
    const emailError = document.getElementById('adminEmailError');
    if (!validateEmail(email.value)) {
        emailError.textContent = 'Please enter a valid admin email address';
        isValid = false;
    } else {
        emailError.textContent = '';
    }
    
    // Password validation
    const password = document.getElementById('adminPassword');
    const passwordError = document.getElementById('adminPasswordError');
    if (!password.value) {
        passwordError.textContent = 'Password is required';
        isValid = false;
    } else {
        passwordError.textContent = '';
    }
    
    // Admin Code validation
    const adminCode = document.getElementById('adminCode');
    const codeError = document.getElementById('adminCodeError');
    if (adminCode.value.length < 4) {
        codeError.textContent = 'Admin code must be at least 4 characters';
        isValid = false;
    } else {
        codeError.textContent = '';
    }
    
    return isValid;
}

// Admin Register Form Validation
const adminRegisterForm = document.getElementById('adminRegisterForm');
if (adminRegisterForm) {
    adminRegisterForm.addEventListener('submit', (e) => {
        // ONLY prevent the form from submitting if validation fails
        if (!validateAdminRegisterForm()) {
            e.preventDefault();
            return;
        }
        
        // If validation passes, let the form submit to the server
        // The PHP backend will handle security code validation and account creation
    });
}

function validateAdminRegisterForm() {
    let isValid = true;
    
    // Full Name validation
    const fullName = document.getElementById('adminFullName');
    const fullNameError = document.getElementById('adminFullNameError');
    if (fullName.value.trim().split(' ').length < 2) {
        fullNameError.textContent = 'Please enter your full name (first and last)';
        isValid = false;
    } else {
        fullNameError.textContent = '';
    }
    
    // Email validation
    const email = document.getElementById('adminRegEmail');
    const emailError = document.getElementById('adminRegEmailError');
    if (!validateEmail(email.value)) {
        emailError.textContent = 'Please enter a valid admin email address';
        isValid = false;
    } else {
        emailError.textContent = '';
    }
    
    // Phone validation
    const phone = document.getElementById('adminPhone');
    const phoneError = document.getElementById('adminPhoneError');
    if (!validatePhone(phone.value)) {
        phoneError.textContent = 'Please enter a valid phone number (at least 10 digits)';
        isValid = false;
    } else {
        phoneError.textContent = '';
    }
    
    // Password validation
    const password = document.getElementById('adminRegPassword');
    const passwordError = document.getElementById('adminRegPasswordError');
    if (!validatePassword(password.value)) {
        passwordError.textContent = 'Password must be at least 8 characters long';
        isValid = false;
    } else {
        passwordError.textContent = '';
    }
    
    // Confirm password validation
    const confirmPassword = document.getElementById('adminRegConfirmPassword');
    const confirmPasswordError = document.getElementById('adminRegConfirmPasswordError');
    if (confirmPassword.value !== password.value) {
        confirmPasswordError.textContent = 'Passwords do not match';
        isValid = false;
    } else {
        confirmPasswordError.textContent = '';
    }
    
    // Security Code validation
    const secCode = document.getElementById('adminSecurityCode');
    const secCodeError = document.getElementById('adminSecurityCodeError');
    if (!secCode.value) {
        secCodeError.textContent = 'Security code is required';
        isValid = false;
    } else {
        secCodeError.textContent = '';
    }
    
    // Terms validation
    const terms = document.querySelector('#adminRegisterForm input[name="terms"]');
    const termsError = document.getElementById('adminTermsError');
    if (!terms || !terms.checked) {
        termsError.textContent = 'You must agree to the terms and responsibilities';
        isValid = false;
    } else {
        termsError.textContent = '';
    }
    
    return isValid;
}

// Admin Logout
const adminLogoutBtns = document.querySelectorAll('#adminLogoutBtn');
adminLogoutBtns.forEach(btn => {
    btn.addEventListener('click', (e) => {
        e.preventDefault();
        // Properly logout on the server
        window.location.href = 'logout.php';
    });
});

// Admin login protection is handled by PHP's require_admin_login() function
// No need for client-side localStorage checks here
