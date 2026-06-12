// AI Assistant Login Form JavaScript
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 AI Assistant Login Form initializing...');
    
    // Get all elements
    const loginForm = document.getElementById('loginForm');
    const firstnameInput = document.getElementById('firstname');
    const lastnameInput = document.getElementById('lastname');
    const cinInput = document.getElementById('cin');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    const passwordToggle = document.getElementById('passwordToggle');
    const submitButton = document.querySelector('.neural-button');
    const successMessage = document.getElementById('successMessage');
    const googleBtn = document.getElementById('googleSignIn');
    const githubBtn = document.getElementById('githubSignIn');
    
    // Debug log
    console.log('📋 Found elements:', {
        loginForm: !!loginForm,
        emailInput: !!emailInput,
        passwordInput: !!passwordInput,
        googleBtn: !!googleBtn,
        githubBtn: !!githubBtn,
        successMessage: !!successMessage
    });

    // Password toggle functionality
    if (passwordToggle && passwordInput) {
        passwordToggle.addEventListener('click', function() {
            const type = passwordInput.type === 'password' ? 'text' : 'password';
            passwordInput.type = type;
            this.classList.toggle('toggle-active', type === 'text');
            console.log('👁️ Password visibility:', type);
        });
    }

    // OAuth buttons - add explicit click handlers to prevent form submission
    if (googleBtn) {
        console.log('✅ Google button found - adding click handler');
        googleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('🔗 Redirecting to Google OAuth...');
            const baseUrl = typeof BASE_URL !== 'undefined' ? BASE_URL : '';
            window.location.href = baseUrl + '/controllers/google-login.php';
            return false;
        });
    }
    if (githubBtn) {
        console.log('✅ GitHub button found - adding click handler');
        githubBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('🔗 Redirecting to GitHub OAuth...');
            const baseUrl = typeof BASE_URL !== 'undefined' ? BASE_URL : '';
            window.location.href = baseUrl + '/controllers/github-login.php';
            return false;
        });
    }

    // Form submission
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            console.log('📝 Form submitted');

            // Validate all fields
            const isValid = validateAllFields();

            if (!isValid) {
                console.log('❌ Form validation failed');
                e.preventDefault();
                return;
            }

            if (submitButton) {
                submitButton.classList.add('loading');
            }

            // Let the form submit naturally to PHP
            // The PHP will handle the redirect
        });
    }

    // Debounce function for real-time validation
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Input validation with debounced real-time feedback
    if (firstnameInput) {
        firstnameInput.addEventListener('blur', validateFirstname);
        firstnameInput.addEventListener('input', debounce(() => {
            clearError('firstname');
            autoFormatName(firstnameInput);
            validateFirstnameRealtime();
        }, 300));
    }

    if (lastnameInput) {
        lastnameInput.addEventListener('blur', validateLastname);
        lastnameInput.addEventListener('input', debounce(() => {
            clearError('lastname');
            autoFormatName(lastnameInput);
            validateLastnameRealtime();
        }, 300));
    }

    if (cinInput) {
        cinInput.addEventListener('blur', validateCin);
        cinInput.addEventListener('input', () => clearError('cin'));
    }

    if (emailInput) {
        emailInput.addEventListener('blur', validateEmail);
        emailInput.addEventListener('input', () => {
            clearError('email');
            autoTrimEmail(emailInput);
        });
    }

    if (passwordInput) {
        passwordInput.addEventListener('blur', validatePassword);
        passwordInput.addEventListener('input', () => {
            clearError('password');
            updatePasswordStrength();
        });
    }

    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('blur', validateConfirmPassword);
        confirmPasswordInput.addEventListener('input', () => clearError('confirmPassword'));
        confirmPasswordInput.addEventListener('paste', function(e) {
            e.preventDefault();
            showError('confirmPassword', 'Pasting is not allowed in the confirm password field.');
        });
    }

    function validateEmail() {
        const email = emailInput.value.trim();

        if (!email) {
            showError('email', 'Email cannot be empty.');
            return false;
        }

        // Prevent SQL injection and XSS
        if (/['";\\<>\-]/.test(email)) {
            showError('email', 'Email contains invalid characters.');
            return false;
        }

        if (/\s/.test(email)) {
            showError('email', 'Email cannot contain spaces.');
            return false;
        }

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            showError('email', 'Invalid email format.');
            return false;
        }

        // Check for disposable emails
        const disposableDomains = ['10minutemail.com', 'mailinator.com', 'guerrillamail.com', 'temp-mail.org', 'yopmail.com', 'temp-mail.com', 'throwaway.email'];
        const domain = email.split('@')[1].toLowerCase();
        if (disposableDomains.includes(domain)) {
            showError('email', 'Disposable emails are not allowed.');
            return false;
        }

        // Domain typo suggestions
        const commonTypos = {
            'gmal.com': 'gmail.com',
            'gmial.com': 'gmail.com',
            'hotmal.com': 'hotmail.com',
            'yaho.com': 'yahoo.com',
            'outlok.com': 'outlook.com'
        };
        if (commonTypos[domain]) {
            showError('email', `Did you mean @${commonTypos[domain]}?`);
            return false;
        }

        // Simulate email existence check (in real app, this would be an API call)
        // For demo purposes, we'll just check if it's a common domain
        const validDomains = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'icloud.com', 'aol.com'];
        if (!validDomains.includes(domain)) {
            showError('email', 'Please use a valid email domain.');
            return false;
        }

        clearError('email');
        return true;
    }

    // Offensive words list
    const offensiveWords = ['fuck', 'shit', 'damn', 'bitch', 'asshole', 'bastard', 'cunt', 'dick', 'pussy', 'cock', 'whore', 'slut', 'nigger', 'faggot', 'retard'];

    function validateFirstname() {
        const firstname = firstnameInput.value.trim();

        if (!firstname) {
            showError('firstname', 'Firstname cannot be empty.');
            return false;
        }

        // Prevent SQL injection and XSS
        if (/['";\\<>\-]/.test(firstname)) {
            showError('firstname', 'Firstname contains invalid characters.');
            return false;
        }

        if (/\s/.test(firstname)) {
            showError('firstname', 'Firstname cannot contain spaces.');
            return false;
        }

        if (/\d/.test(firstname)) {
            showError('firstname', 'Firstname cannot contain numbers.');
            return false;
        }

        // Allow accents, hyphens, apostrophes for internationalization
        if (/[^a-zA-ZÀ-ÿ\-']/.test(firstname)) {
            showError('firstname', 'Firstname cannot contain symbols.');
            return false;
        }

        if (!/^[a-zA-ZÀ-ÿ\-']+$/.test(firstname)) {
            showError('firstname', 'Firstname must contain only letters.');
            return false;
        }

        if (firstname.length < 2 || firstname.length > 20) {
            showError('firstname', 'Firstname must be between 2 and 20 characters.');
            return false;
        }

        // Check for offensive words
        if (offensiveWords.some(word => firstname.toLowerCase().includes(word))) {
            showError('firstname', 'Firstname contains inappropriate content.');
            return false;
        }

        // Prevent consecutive repeating characters
        if (/(.)\1{3,}/.test(firstname)) {
            showError('firstname', 'Firstname cannot have consecutive repeating characters.');
            return false;
        }

        // No excessive capitalization
        const upperCount = (firstname.match(/[A-Z]/g) || []).length;
        if (upperCount > firstname.length / 2 && firstname.length > 3) {
            showError('firstname', 'Firstname has excessive capitalization.');
            return false;
        }

        // Combined length check with lastname
        const lastname = lastnameInput.value.trim();
        if (lastname && (firstname.length + lastname.length) > 50) {
            showError('firstname', 'Combined firstname and lastname cannot exceed 50 characters.');
            return false;
        }

        clearError('firstname');
        return true;
    }

    function validateLastname() {
        const lastname = lastnameInput.value.trim();

        if (!lastname) {
            showError('lastname', 'Lastname cannot be empty.');
            return false;
        }

        // Prevent SQL injection and XSS
        if (/['";\\<>\-]/.test(lastname)) {
            showError('lastname', 'Lastname contains invalid characters.');
            return false;
        }

        if (/\s/.test(lastname)) {
            showError('lastname', 'Lastname cannot contain spaces.');
            return false;
        }

        if (/\d/.test(lastname)) {
            showError('lastname', 'Lastname cannot contain numbers.');
            return false;
        }

        // Allow accents, hyphens, apostrophes for internationalization
        if (/[^a-zA-ZÀ-ÿ\-']/.test(lastname)) {
            showError('lastname', 'Lastname cannot contain symbols.');
            return false;
        }

        if (!/^[a-zA-ZÀ-ÿ\-']+$/.test(lastname)) {
            showError('lastname', 'Lastname must contain only letters.');
            return false;
        }

        if (lastname.length < 2 || lastname.length > 20) {
            showError('lastname', 'Lastname must be between 2 and 20 characters.');
            return false;
        }

        // Check for offensive words
        if (offensiveWords.some(word => lastname.toLowerCase().includes(word))) {
            showError('lastname', 'Lastname contains inappropriate content.');
            return false;
        }

        // Prevent consecutive repeating characters
        if (/(.)\1{3,}/.test(lastname)) {
            showError('lastname', 'Lastname cannot have consecutive repeating characters.');
            return false;
        }

        // No excessive capitalization
        const upperCount = (lastname.match(/[A-Z]/g) || []).length;
        if (upperCount > lastname.length / 2 && lastname.length > 3) {
            showError('lastname', 'Lastname has excessive capitalization.');
            return false;
        }

        clearError('lastname');
        return true;
    }

    function validateCin() {
        const cin = cinInput.value.trim();

        if (!cin) {
            showError('cin', 'CIN cannot be empty.');
            return false;
        }

        if (/\s/.test(cin)) {
            showError('cin', 'CIN cannot contain spaces.');
            return false;
        }

        if (!/^\d{8}$/.test(cin)) {
            showError('cin', 'CIN must contain exactly 8 digits.');
            return false;
        }

        if (!/^\d+$/.test(cin)) {
            showError('cin', 'CIN must contain digits only.');
            return false;
        }

        // Check for repetitive digits
        if (/(.)\1{7}/.test(cin)) {
            showError('cin', 'CIN cannot have all repetitive digits.');
            return false;
        }

        clearError('cin');
        return true;
    }

    function validatePassword() {
        const password = passwordInput.value;
        const firstname = firstnameInput.value.trim().toLowerCase();
        const lastname = lastnameInput.value.trim().toLowerCase();
        const cin = cinInput.value.trim();
        const email = emailInput.value.trim().toLowerCase();

        if (!password) {
            showError('password', 'Password cannot be empty.');
            return false;
        }

        // Prevent SQL injection and XSS
        if (/['";\\<>\-]/.test(password)) {
            showError('password', 'Password contains invalid characters.');
            return false;
        }

        if (/\s/.test(password)) {
            showError('password', 'Password cannot contain spaces.');
            return false;
        }

        if (password.length < 8) {
            showError('password', 'Password must be at least 8 characters long.');
            return false;
        }

        if (password.length > 128) {
            showError('password', 'Password cannot exceed 128 characters.');
            return false;
        }

        if (!/[A-Z]/.test(password)) {
            showError('password', 'Password must contain at least one uppercase letter.');
            return false;
        }

        if (!/[a-z]/.test(password)) {
            showError('password', 'Password must contain at least one lowercase letter.');
            return false;
        }

        if (!/\d/.test(password)) {
            showError('password', 'Password must contain at least one digit.');
            return false;
        }

        if (!/[@$!%*?&.\-_]/.test(password)) {
            showError('password', 'Password must contain at least one special character.');
            return false;
        }

        // Enhanced entropy calculation
        const entropy = calculatePasswordEntropy(password);
        if (entropy < 50) {
            showError('password', 'Password is too weak. Please use a more complex combination.');
            return false;
        }

        // Prevent common passwords
        const commonPasswords = ['123456', 'password', '123456789', 'qwerty', 'abc123', 'password123', 'admin', 'letmein', 'welcome', 'monkey', 'dragon', 'master', 'sunshine', 'princess', 'flower', 'superman', 'batman', 'trustno1'];
        if (commonPasswords.includes(password.toLowerCase())) {
            showError('password', 'This password is too common. Please choose a stronger one.');
            return false;
        }

        // Prevent repeating sequences (enhanced)
        if (/(.)\1{2,}/.test(password) || /(.{2,})\1{2,}/.test(password)) {
            showError('password', 'Password cannot contain repeating sequences.');
            return false;
        }

        // Prevent keyboard patterns (enhanced)
        const keyboardPatterns = ['qwerty', 'asdf', 'zxcv', '1234', 'abcd', 'qazwsx', 'wsxedc', '098765', 'mnbvcxz', 'lkjhgf', 'poiuyt', '0987654321'];
        if (keyboardPatterns.some(pattern => password.toLowerCase().includes(pattern))) {
            showError('password', 'Password cannot contain common keyboard patterns.');
            return false;
        }

        // Prevent sequential patterns
        if (/(?:012|123|234|345|456|567|678|789|890|abc|bcd|cde|def|efg|fgh|ghi|hij|ijk|jkl|klm|lmn|mno|nop|opq|pqr|qrs|rst|stu|tuv|uvw|vwx|wxy|xyz)/.test(password.toLowerCase())) {
            showError('password', 'Password cannot contain sequential patterns.');
            return false;
        }

        // Ensure diverse symbols (not just one type)
        const specialChars = password.match(/[@$!%*?&.\-_]/g) || [];
        if (specialChars.length < 2 && password.length >= 12) {
            showError('password', 'Password should contain at least 2 different special characters for better security.');
            return false;
        }

        // Prevent similarity to personal info (enhanced)
        const lowerPassword = password.toLowerCase();
        const personalInfo = [firstname, lastname, cin, email.split('@')[0], email.split('@')[1]];
        if (personalInfo.some(info => info && lowerPassword.includes(info))) {
            showError('password', 'Password cannot be similar to your personal information.');
            return false;
        }

        // Check for dictionary words
        const dictionaryWords = ['password', 'admin', 'user', 'login', 'welcome', 'system', 'access', 'account', 'secure', 'secret', 'private', 'public', 'test', 'demo', 'temp', 'guest'];
        if (dictionaryWords.some(word => lowerPassword.includes(word))) {
            showError('password', 'Password cannot contain common dictionary words.');
            return false;
        }

        clearError('password');
        return true;
    }

    // Calculate password entropy
    function calculatePasswordEntropy(password) {
        const charSets = {
            lowercase: /[a-z]/,
            uppercase: /[A-Z]/,
            digits: /\d/,
            special: /[@$!%*?&.\-_]/
        };

        let poolSize = 0;
        Object.values(charSets).forEach(regex => {
            if (regex.test(password)) poolSize += regex === charSets.lowercase || regex === charSets.uppercase ? 26 : regex === charSets.digits ? 10 : 8;
        });

        return Math.log2(Math.pow(poolSize, password.length));
    }

    // Real-time validation functions (less strict for better UX)
    function validateFirstnameRealtime() {
        const firstname = firstnameInput.value.trim();

        if (!firstname) {
            clearError('firstname');
            return;
        }

        // Basic checks for real-time feedback
        if (/\s/.test(firstname)) {
            showError('firstname', 'Firstname cannot contain spaces.');
            return;
        }

        if (/\d/.test(firstname)) {
            showError('firstname', 'Firstname cannot contain numbers.');
            return;
        }

        if (/[^a-zA-ZÀ-ÿ\-']/.test(firstname)) {
            showError('firstname', 'Firstname cannot contain symbols.');
            return;
        }

        if (firstname.length > 20) {
            showError('firstname', 'Firstname must be between 2 and 20 characters.');
            return;
        }

        clearError('firstname');
    }

    function validateLastnameRealtime() {
        const lastname = lastnameInput.value.trim();

        if (!lastname) {
            clearError('lastname');
            return;
        }

        // Basic checks for real-time feedback
        if (/\s/.test(lastname)) {
            showError('lastname', 'Lastname cannot contain spaces.');
            return;
        }

        if (/\d/.test(lastname)) {
            showError('lastname', 'Lastname cannot contain numbers.');
            return;
        }

        if (/[^a-zA-ZÀ-ÿ\-']/.test(lastname)) {
            showError('lastname', 'Lastname cannot contain symbols.');
            return;
        }

        if (lastname.length > 20) {
            showError('lastname', 'Lastname must be between 2 and 20 characters.');
            return;
        }

        clearError('lastname');
    }

    function validateConfirmPassword() {
        const confirmPassword = confirmPasswordInput.value;
        const password = passwordInput.value;

        if (!confirmPassword) {
            showError('confirmPassword', 'Please confirm your password.');
            return false;
        }

        if (confirmPassword !== password) {
            showError('confirmPassword', 'Passwords do not match.');
            return false;
        }

        clearError('confirmPassword');
        return true;
    }

    function validateAllFields() {
        const validations = [
            validateFirstname(),
            validateLastname(),
            validateCin(),
            validateEmail(),
            validatePassword(),
            validateConfirmPassword()
        ];

        return validations.every(valid => valid);
    }

    function showError(field, message) {
        const errorElement = document.getElementById(`${field}Error`);
        const smartField = document.getElementById(field)?.closest('.smart-field');
        
        if (smartField) smartField.classList.add('error');
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.classList.add('show');
        }
    }

    function clearError(field) {
        const errorElement = document.getElementById(`${field}Error`);
        const smartField = document.getElementById(field)?.closest('.smart-field');
        
        if (smartField) smartField.classList.remove('error');
        if (errorElement) {
            errorElement.classList.remove('show');
        }
    }

    function handleSocialLogin(provider, button) {
        console.log(`🔗 Starting ${provider} login...`);
        
        // Save original content
        const originalHTML = button.innerHTML;
        
        // Show loading state
        button.innerHTML = `
            <div class="social-bg"></div>
            <div style="display:flex; align-items:center; gap:4px;">
                <div style="width:3px; height:12px; background:currentColor; border-radius:1px; animation:neuralSpinner 1.2s ease-in-out infinite"></div>
                <div style="width:3px; height:12px; background:currentColor; border-radius:1px; animation:neuralSpinner 1.2s ease-in-out infinite; animation-delay:0.1s"></div>
                <div style="width:3px; height:12px; background:currentColor; border-radius:1px; animation:neuralSpinner 1.2s ease-in-out infinite; animation-delay:0.2s"></div>
            </div>
            <span>Connecting to ${provider}...</span>
            <div class="social-glow"></div>
        `;
        button.style.opacity = '0.7';
        button.disabled = true;

        // Simulate API call
        setTimeout(() => {
            console.log(`✅ ${provider} login successful`);
            showSuccess(`Connected via ${provider}!`);
            
            // Reset button (optional since we're hiding everything)
            button.innerHTML = originalHTML;
            button.style.opacity = '1';
            button.disabled = false;
        }, 2000);
    }

    function showSuccess(message) {
        console.log('🎉 Showing success:', message);
        
        // Hide all form elements
        const elementsToHide = [
            '.login-form',
            '.neural-social', 
            '.signup-section',
            '.auth-separator'
        ];
        
        elementsToHide.forEach(selector => {
            const element = document.querySelector(selector);
            if (element) {
                element.style.display = 'none';
                console.log(`👻 Hid: ${selector}`);
            }
        });
        
        // Show success message
        if (successMessage) {
            const title = successMessage.querySelector('h3');
            if (title) {
                title.textContent = message;
            }
            successMessage.classList.add('show');
            console.log('✨ Success message displayed');
        }
        
        // Optional: Redirect after delay
        setTimeout(() => {
            console.log('🔄 Ready for redirect to dashboard...');
            // window.location.href = '/dashboard';
        }, 3000);
    }

    // Helper functions
    function autoFormatName(input) {
        let value = input.value;
        // Trim spaces
        value = value.trim();
        // Remove double spaces
        value = value.replace(/\s+/g, ' ');
        // Capitalize first letter
        value = value.charAt(0).toUpperCase() + value.slice(1).toLowerCase();
        input.value = value;
    }

    function autoTrimEmail(input) {
        input.value = input.value.trim();
    }

    function updatePasswordStrength() {
        const password = passwordInput.value;
        let strength = 0;
        let hints = [];

        if (password.length >= 8) strength++;
        else hints.push('At least 8 characters');

        if (/[A-Z]/.test(password)) strength++;
        else hints.push('1 uppercase letter');

        if (/[a-z]/.test(password)) strength++;
        else hints.push('1 lowercase letter');

        if (/\d/.test(password)) strength++;
        else hints.push('1 digit');

        if (/[@$!%*?&.\-_]/.test(password)) strength++;
        else hints.push('1 special character');

        if (password.length >= 12) strength++;

        // Update UI
        const strengthIndicator = document.getElementById('passwordStrength');
        if (!strengthIndicator) {
            const indicator = document.createElement('div');
            indicator.id = 'passwordStrength';
            indicator.style.cssText = `
                position: absolute;
                top: -25px;
                left: 0;
                font-size: 12px;
                font-weight: 500;
                padding: 4px 8px;
                border-radius: 4px;
                transition: all 0.2s ease;
                color: #f8fafc;
            `;
            passwordInput.parentNode.appendChild(indicator);
        }

        const indicator = document.getElementById('passwordStrength');
        if (indicator) {
            let strengthText = '';
            if (strength <= 2) {
                strengthText = 'Weak';
            } else if (strength <= 4) {
                strengthText = 'Medium';
            } else {
                strengthText = 'Strong';
            }
            indicator.textContent = `Strength: ${strengthText}`;
            indicator.className = `password-strength-${strengthText.toLowerCase()}`;
        }

        // Show hints if not strong
        const hintsElement = document.getElementById('passwordHints');
        if (hintsElement) {
            if (hints.length > 0) {
                hintsElement.textContent = `Password must contain: ${hints.join(', ')}`;
                hintsElement.style.display = 'block';
            } else {
                hintsElement.style.display = 'none';
            }
        }
    }

    // Check for success parameter in URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('success') === '1') {
        showSuccess('Account created successfully!');
        setTimeout(() => {
            window.location.href = 'login.php';
        }, 3000);
    } else if (urlParams.get('error')) {
        const errorMsg = urlParams.get('error') === '1' ? 'Error creating account!' : decodeURIComponent(urlParams.get('error'));
        alert(errorMsg);
    }

    console.log('✅ AI Assistant Login Form initialized successfully!');
    console.log('👉 Try clicking the Google or GitHub buttons!');
});
