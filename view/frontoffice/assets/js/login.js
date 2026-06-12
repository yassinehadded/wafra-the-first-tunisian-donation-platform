// Wait for page to load before running code
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 AI Assistant Login Form initializing...');

    // Get form elements from the page
    const loginForm = document.getElementById('loginForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const passwordToggle = document.getElementById('passwordToggle');
    const submitButton = document.getElementById('loginSubmitBtn') || document.querySelector('.neural-button');
    const successMessage = document.getElementById('successMessage');
    const googleBtn = document.getElementById('googleSignIn');
    const githubBtn = document.getElementById('githubSignIn');

    // Track if form is valid and setup timers for delayed validation
    let isFormValid = false;
    let debounceTimerEmail = null;
    let debounceTimerPassword = null;
    let loginAttempts = 0;
    let lastLoginAttempt = 0;

    // List of temporary email services that are not allowed
    const disposableDomains = [
        '10minutemail.com','mailinator.com','guerrillamail.com','temp-mail.org','yopmail.com',
        'temp-mail.com','throwaway.email','maildrop.cc','tempail.com','dispostable.com'
    ];

    // List of common weak passwords that are not allowed
    const commonPasswords = [
        '123456','password','123456789','qwerty','abc123','password123','admin','letmein',
        'welcome','monkey','1234567890','iloveyou','princess','rockyou','1234567',
        '12345678','sunshine','qwerty123','dragon','master','flower','superman','batman','trustno1'
    ];

    // Common typos in email domains and their corrections
    const emailTypos = {
        'gmal.com': 'gmail.com',
        'gmial.com': 'gmail.com',
        'gmai.com': 'gmail.com',
        'hotmai.com': 'hotmail.com',
        'hotmial.com': 'hotmail.com',
        'yahho.com': 'yahoo.com',
        'yaho.com': 'yahoo.com',
        'outlok.com': 'outlook.com'
    };

    // List of offensive words that cannot be used
    const offensiveWords = [
        'fuck', 'shit', 'damn', 'bitch', 'asshole', 'bastard', 'cunt',
        'dick', 'pussy', 'cock', 'whore', 'slut', 'nigger', 'faggot', 'retard'
    ];

    // Patterns to detect dangerous characters that could be used for attacks
    const invalidCharsRegex = /['";\\<>\-]/;
    const sqlInjectionRegex = /--/;

    // Function to delay validation until user stops typing
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

    // Clean input by making it lowercase and removing special characters
    function sanitizeInput(input) {
        return input.toLowerCase().replace(/[^a-z0-9]/g, '').trim();
    }



    // Check if input contains any bad words
function containsBadWord(input) {
    let sanitized = sanitizeInput(input);

    // Check if any offensive word appears in the input
    for (const word of offensiveWords) {
        if (sanitized.includes(word)) return true;
    }
    return false;
}

    // Validate email address format and content
    function validateEmail(showErrors = true) {
        const email = emailInput?.value?.trim() || '';
        let isValid = true;
        let errorMessage = '';

        // Check if email is provided
        if (!email) {
            isValid = false;
            errorMessage = 'Email address is required';
        }
        // Check for dangerous characters
        else if (invalidCharsRegex.test(email)) {
            isValid = false;
            errorMessage = 'Email contains invalid characters';
        }
        else if (sqlInjectionRegex.test(email)) {
            isValid = false;
            errorMessage = 'Invalid email format';
        }
        // Check if email has spaces
        else if (/\s/.test(email)) {
            isValid = false;
            errorMessage = 'Email cannot contain spaces';
        }
        else {
            // Check if email has correct format with @ and domain
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                isValid = false;
                errorMessage = 'Please enter a valid email address';
            } else {
                // Get the domain part after @ symbol
                const domain = email.split('@')[1]?.toLowerCase();
                if (!domain) {
                    isValid = false;
                    errorMessage = 'Email domain is invalid';
                }
                // Check if email uses temporary email service
                else if (disposableDomains.includes(domain)) {
                    isValid = false;
                    errorMessage = 'Disposable email addresses are not allowed';
                }
                // Suggest correction for common typos
                else if (emailTypos[domain]) {
                    isValid = false;
                    errorMessage = `Did you mean ${email.split('@')[0]}@${emailTypos[domain]}?`;
                }
            }
        }

        // Check if email contains bad words
        if (isValid && containsBadWord(email)) {
            isValid = false;
            errorMessage = 'Email or password cannot contain badwords';
        }

        // Show or hide error message
        if (!isValid && showErrors) {
            showError('email', errorMessage);
        } else if (isValid) {
            clearError('email');
        }

        return isValid;
    }

    // Calculate how strong the password is based on character variety
    function calculatePasswordEntropy(password) {
        // Check what types of characters are used
        const charSets = {
            lowercase: /[a-z]/,
            uppercase: /[A-Z]/,
            digits: /\d/,
            special: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/,
        };
        let poolSize = 0;
        Object.values(charSets).forEach(regex => {
            if (regex.test(password)) {
                if (regex === charSets.lowercase || regex === charSets.uppercase) poolSize += 26;
                else if (regex === charSets.digits) poolSize += 10;
                else if (regex === charSets.special) poolSize += 32;
            }
        });
        if (poolSize === 0) return 0;
        return Math.log2(Math.pow(poolSize, password.length));
    }

    // Validate password meets basic requirements
    function validatePassword(showErrors = true) {
        const password = passwordInput?.value || '';
        let isValid = true;
        let errorMessage = '';

        // Check if password is provided
        if (!password) {
            isValid = false;
            errorMessage = 'Password is required';
        }
        // Check for dangerous characters
        else if (invalidCharsRegex.test(password)) {
            isValid = false;
            errorMessage = 'Password contains invalid characters';
        }
        else if (sqlInjectionRegex.test(password)) {
            isValid = false;
            errorMessage = 'Password contains invalid characters';
        }
        // Check if password contains bad words
        else if (containsBadWord(password)) {
            isValid = false;
            errorMessage = 'Password cannot contain inappropriate words';
        }

        // Show or hide error message
        if (!isValid && showErrors) {
            showError('password', errorMessage);
        } else if (isValid) {
            clearError('password');
        }

        return isValid;
    }

    // Update the visual strength indicator for password
    function updatePasswordStrength() {
        const password = passwordInput?.value || '';
        let strength = 0;
        let hints = [];

        // Check password length
        if (password.length >= 8) strength++;
        else hints.push('At least 8 characters');

        // Check for uppercase letter
        if (/[A-Z]/.test(password)) strength++;
        else hints.push('1 uppercase letter');

        // Check for lowercase letter
        if (/[a-z]/.test(password)) strength++;
        else hints.push('1 lowercase letter');

        // Check for number
        if (/\d/.test(password)) strength++;
        else hints.push('1 digit');

        // Check for special character
        if (/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) strength++;
        else hints.push('1 special character');

        // Bonus for longer password
        if (password.length >= 12) strength++;

        // Determine strength level
        let strengthText;
        if (strength <= 2) strengthText = 'Weak';
        else if (strength <= 4) strengthText = 'Medium';
        else strengthText = 'Strong';

        // Create or update strength indicator element
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

        // Update strength text
        const indicator = document.getElementById('passwordStrength');
        if (indicator) {
            indicator.textContent = `Strength: ${strengthText}`;
            indicator.className = `password-strength-${strengthText.toLowerCase()}`;
        }

        // Show hints if password is weak
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

    // Check if entire form is valid and enable/disable submit button
    function updateFormValidity() {
        const emailValid = validateEmail(false);
        const passwordValid = validatePassword(false);
        isFormValid = emailValid && passwordValid;

        // Enable or disable submit button based on form validity
        if (submitButton) {
            submitButton.disabled = !isFormValid;
            submitButton.style.opacity = isFormValid ? '1' : '0.6';
        }
    }

    // Move cursor to first field with error
    function focusFirstInvalidField() {
        if (!validateEmail(false)) {
            emailInput?.focus();
        } else if (!validatePassword(false)) {
            passwordInput?.focus();
        }
    }

    // Display error message below the field
    function showError(field, message) {
        const errorElement = document.getElementById(`${field}Error`);
        const smartField = document.getElementById(field)?.closest('.smart-field');

        // Add red border to field
        if (smartField) smartField.classList.add('error');

        // Show error message with fade in effect
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.classList.add('show');
            errorElement.style.opacity = '0';
            setTimeout(() => {
                errorElement.style.opacity = '1';
            }, 50);
        }
    }

    // Remove error message and red border
    function clearError(field) {
        const errorElement = document.getElementById(`${field}Error`);
        const smartField = document.getElementById(field)?.closest('.smart-field');

        // Remove red border
        if (smartField) smartField.classList.remove('error');

        // Hide error message with fade out effect
        if (errorElement) {
            errorElement.style.opacity = '1';
            setTimeout(() => {
                errorElement.style.opacity = '0';
            }, 50);
            setTimeout(() => {
                errorElement.classList.remove('show');
                errorElement.textContent = '';
            }, 300);
        }
    }

    // Handle Google or GitHub login button click
    function handleSocialLogin(provider, button) {
        console.log(`🔗 Starting ${provider} login...`);

        // Save original button text
        const originalHTML = button.innerHTML;

        // Show loading spinner on button
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

        // Simulate connection delay
        setTimeout(() => {
            console.log(`✅ ${provider} login successful`);
            showSuccess(`Connected via ${provider}!`);

            // Restore original button
            button.innerHTML = originalHTML;
            button.style.opacity = '1';
            button.disabled = false;
        }, 2000);
    }

    // Show success message and hide login form
    function showSuccess(message) {
        console.log('🎉 Showing success:', message);

        // Hide form elements
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

        // Display success message
        if (successMessage) {
            const title = successMessage.querySelector('h3');
            if (title) title.textContent = message;
            successMessage.classList.add('show');
            console.log('✨ Success message displayed');
        }

        // Wait before redirecting
        setTimeout(() => {
            console.log('🔄 Ready for redirect to dashboard...');
            // window.location.href = '/dashboard';
        }, 3000);
    }

    // Check if Caps Lock is on while typing password
    if (passwordInput) {
        passwordInput.addEventListener('keydown', function(e) {
            const capsLockOn = e.getModifierState && e.getModifierState('CapsLock');
            const capsWarning = document.getElementById('capsWarning');
            // Show warning if Caps Lock is on
            if (capsLockOn) {
                if (!capsWarning) {
                    const warning = document.createElement('div');
                    warning.id = 'capsWarning';
                    warning.textContent = '⚠️ Caps Lock is on';
                    warning.style.cssText = `
                        position: absolute;
                        top: -25px;
                        right: 0;
                        color: #fbbf24;
                        font-size: 12px;
                        font-weight: 500;
                        background: rgba(251, 191, 36, 0.1);
                        padding: 4px 8px;
                        border-radius: 4px;
                        border: 1px solid rgba(251, 191, 36, 0.3);
                    `;
                    passwordInput.parentNode.appendChild(warning);
                }
            } else if (capsWarning) {
                capsWarning.remove();
            }
        });
    }

    // Validate email when user types
    if (emailInput) {
        emailInput.addEventListener('input', () => {
            clearError('email');
            // Wait for user to stop typing before validating
            if(debounceTimerEmail) clearTimeout(debounceTimerEmail);
            debounceTimerEmail = setTimeout(() => {
                validateEmail(false);
                updateFormValidity();
            }, 300);
        });
        // Validate when user leaves email field
        emailInput.addEventListener('blur', () => {
            validateEmail(true);
            updateFormValidity();
        });
    }

    // Validate password when user types
    if (passwordInput) {
        passwordInput.addEventListener('input', () => {
            clearError('password');
            // Wait for user to stop typing before validating
            if(debounceTimerPassword) clearTimeout(debounceTimerPassword);
            debounceTimerPassword = setTimeout(() => {
                validatePassword(false);
                updateFormValidity();
            }, 300);
        });
        // Validate when user leaves password field
        passwordInput.addEventListener('blur', () => {
            validatePassword(true);
            updateFormValidity();
        });
    }

    // Check URL for error messages to display
    function checkUrlParameters() {
        const urlParams = new URLSearchParams(window.location.search);

        // Show appropriate error message based on URL parameter
        if (urlParams.get('error') === 'unverified') {
            showGenericError('Please verify your email before logging in. Check your email for the verification link.');
        } else if (urlParams.get('error') === 'invalid') {
            showGenericError('Invalid email or password. Please try again.');
        } else if (urlParams.get('error') === 'badwords') {
            showGenericError('Email or password cannot contain badwords.');
        }
    }

    // Display error or success message at top of form
    function showGenericError(message, isSuccess = false) {
        let genericError = document.getElementById('genericError');
        // Create error element if it doesn't exist
        if (!genericError) {
            genericError = document.createElement('div');
            genericError.id = 'genericError';
            loginForm.insertBefore(genericError, loginForm.firstChild);
        }
        
        // Update styles based on message type
        if (isSuccess) {
            genericError.style.cssText = `
                background: rgba(34, 197, 94, 0.1);
                border: 1px solid rgba(34, 197, 94, 0.3);
                color: #22c55e;
                padding: 12px;
                border-radius: 8px;
                margin-bottom: 20px;
                font-size: 14px;
                font-weight: 500;
                text-align: center;
            `;
        } else {
            genericError.style.cssText = `
                background: rgba(239, 68, 68, 0.1);
                border: 1px solid rgba(239, 68, 68, 0.3);
                color: #ef4444;
                padding: 12px;
                border-radius: 8px;
                margin-bottom: 20px;
                font-size: 14px;
                font-weight: 500;
                text-align: center;
            `;
        }
        
        genericError.textContent = message;
        genericError.style.display = 'block';

        // Hide message after 5 seconds (or longer for success)
        const hideDelay = isSuccess ? 3000 : 5000;
        setTimeout(() => {
            if (genericError) genericError.style.display = 'none';
        }, hideDelay);
    }

    // Handle form submission when user clicks login button
    if (loginForm) {
        console.log('✅ Login form found, attaching submit handler');
        console.log('Form action:', loginForm.action);
        console.log('Form method:', loginForm.method);
        
        loginForm.addEventListener('submit', function(e) {
            console.log('📝 Form submit event triggered');
            console.log('Email value:', emailInput?.value);
            console.log('Password value length:', passwordInput?.value?.length);

            // Prevent default form submission
            e.preventDefault();

            // Get form values
            const email = emailInput?.value?.trim() || '';
            const password = passwordInput?.value || '';

            // Check if fields are filled
            if (!email || !password) {
                console.log('❌ Email or password empty');
                showGenericError('Email and password are required.');
                return false;
            }

            // Check if email has basic format
            if (!email.includes('@') || !email.includes('.')) {
                console.log('❌ Invalid email format');
                showGenericError('Please enter a valid email address.');
                return false;
            }

            // Validate form fields
            if (!validateEmail(true) || !validatePassword(true)) {
                console.log('❌ Form validation failed');
                focusFirstInvalidField();
                return false;
            }

            console.log('✅ Validation passed - Generating reCAPTCHA token...');
            
            // Disable button to prevent clicking twice
            if (submitButton) {
                submitButton.disabled = true;
                const buttonText = submitButton.querySelector('.button-text');
                if (buttonText) buttonText.textContent = 'Verifying...';
            }

            // Generate reCAPTCHA token before submitting
            // Get site key from window.RECAPTCHA_SITE_KEY (set by PHP from .env file)
            const recaptchaSiteKey = window.RECAPTCHA_SITE_KEY || 'SITE_KEY_HERE';
            
            if (!recaptchaSiteKey || recaptchaSiteKey === 'SITE_KEY_HERE') {
                console.error('❌ reCAPTCHA Site Key not configured');
                showGenericError('Security verification is not properly configured. Please contact the administrator.');
                if (submitButton) {
                    submitButton.disabled = false;
                    const buttonText = submitButton.querySelector('.button-text');
                    if (buttonText) buttonText.textContent = 'Initialize Connection';
                }
                return false;
            }
            
            if (typeof grecaptcha !== 'undefined' && grecaptcha.ready) {
                grecaptcha.ready(function() {
                    grecaptcha.execute(recaptchaSiteKey, { action: 'login' })
                        .then(function(token) {
                            console.log('✅ reCAPTCHA token generated');
                            
                            // Set the token in the hidden input
                            const recaptchaInput = document.getElementById('recaptcha_token');
                            if (recaptchaInput) {
                                recaptchaInput.value = token;
            }
            
                            // Submit form via AJAX to handle JSON response
                            console.log('📤 Submitting form with reCAPTCHA token');
                            
                            // Create FormData object
                            const formData = new FormData(loginForm);
                            
                            // Submit via fetch API to handle JSON response
                            fetch(loginForm.action, {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => {
                                console.log('📥 Response status:', response.status, response.statusText);
                                
                                // Check if response is OK
                                if (!response.ok) {
                                    console.error('❌ Response not OK:', response.status, response.statusText);
                                    // Try to get error message from response
                                    return response.text().then(text => {
                                        console.error('Response body:', text.substring(0, 500));
                                        // Try to parse as JSON if possible
                                        try {
                                            const jsonData = JSON.parse(text);
                                            return jsonData;
                                        } catch (e) {
                                            throw new Error('Server error (HTTP ' + response.status + '): ' + response.statusText);
                                        }
                                    });
                                }
                                
                                // Check if response is JSON
                                const contentType = response.headers.get('content-type');
                                if (contentType && contentType.includes('application/json')) {
                                    return response.json();
                                } else {
                                    // If not JSON, log warning
                                    console.warn('⚠️ Response is not JSON, content-type:', contentType);
                                    return response.text().then(text => {
                                        console.error('Non-JSON response:', text.substring(0, 500));
                                        throw new Error('Server returned non-JSON response. Please check server configuration.');
                                    });
                                }
                            })
                            .then(data => {
                                if (!data) {
                                    throw new Error('No data received from server');
                                }
                                
                                if (data.success) {
                                    // Login successful
                                    console.log('✅ Login successful');
                                    console.log('Redirect URL:', data.redirect);
                                    showGenericError(data.message || 'Login successful! Redirecting...', true);
                                    
                                    // Redirect to the provided URL or default
                                    if (data.redirect) {
                                        setTimeout(() => {
                                            console.log('Redirecting to:', data.redirect);
                                            window.location.href = data.redirect;
                                        }, 1000);
                                    } else {
                                        // Default redirect after successful login
                                        console.log('No redirect URL provided, using default');
                                        setTimeout(() => {
                                            window.location.href = '../view/frontoffice/index.php';
                                        }, 1000);
                                    }
                                } else {
                                    // Login failed
                                    console.error('❌ Login failed:', data.message);
                                    showGenericError(data.message || 'Login failed. Please try again.');
                                    
                                    // Re-enable button
                                    if (submitButton) {
                                        submitButton.disabled = false;
                                        const buttonText = submitButton.querySelector('.button-text');
                                        if (buttonText) buttonText.textContent = 'Initialize Connection';
                                    }
                                }
                            })
                            .catch(function(error) {
                                console.error('❌ Form submission error:', error);
                                console.error('Error details:', {
                                    message: error.message,
                                    stack: error.stack,
                                    name: error.name
                                });
                                
                                // More informative error message
                                let errorMessage = 'An error occurred. Please try again.';
                                if (error.message) {
                                    if (error.message.includes('Failed to fetch') || error.message.includes('NetworkError')) {
                                        errorMessage = 'Network error. Please check your internet connection and try again.';
                                    } else if (error.message.includes('JSON')) {
                                        errorMessage = 'Server returned an invalid response. Please try again or contact support.';
                                    } else {
                                        errorMessage = 'Error: ' + error.message;
                                    }
                                }
                                
                                showGenericError(errorMessage);
                                
                                // Re-enable button
                                if (submitButton) {
                                    submitButton.disabled = false;
                                    const buttonText = submitButton.querySelector('.button-text');
                                    if (buttonText) buttonText.textContent = 'Initialize Connection';
                                }
                            });
                        })
                        .catch(function(error) {
                            console.error('❌ reCAPTCHA error:', error);
                            showGenericError('Security verification failed. Please refresh the page and try again.');
                            
                            // Re-enable button
                            if (submitButton) {
                                submitButton.disabled = false;
                                const buttonText = submitButton.querySelector('.button-text');
                                if (buttonText) buttonText.textContent = 'Initialize Connection';
                            }
                        });
                });
            } else {
                console.error('❌ reCAPTCHA not loaded');
                showGenericError('Security verification failed to load. Please refresh the page and try again.');
                
                // Re-enable button
                if (submitButton) {
                    submitButton.disabled = false;
                    const buttonText = submitButton.querySelector('.button-text');
                    if (buttonText) buttonText.textContent = 'Initialize Connection';
                }
            }
            
            return false;
        });
    } else {
        console.error('❌ CRITICAL: loginForm element not found!');
        alert('Form element not found. Check console for errors.');
    }

    // Check if OAuth buttons exist
    console.log('🔍 Checking OAuth buttons:');
    console.log('Google button:', googleBtn);
    console.log('GitHub button:', githubBtn);
    
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

    // Run initial checks when page loads
    checkUrlParameters();
    updateFormValidity();


});
