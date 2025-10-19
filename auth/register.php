<?php
/**
 * Registration Page
 * User registration with validation and security
 */

require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../classes/User.php';
require_once '../classes/RoleManager.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $roleManager = new RoleManager(null);
    redirect($roleManager->getDashboardUrl($_SESSION['user_role']));
}

$errors = [];
$formData = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'role' => 'patient'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        // Sanitize and collect form data
        $formData = [
            'first_name' => sanitizeInput($_POST['first_name'] ?? ''),
            'last_name' => sanitizeInput($_POST['last_name'] ?? ''),
            'email' => sanitizeInput($_POST['email'] ?? ''),
            'phone' => sanitizeInput($_POST['phone'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'confirm_password' => $_POST['confirm_password'] ?? '',
            'role' => sanitizeInput($_POST['role'] ?? 'patient')
        ];
        
        // Basic validation
        if (empty($formData['first_name']) || empty($formData['last_name']) || 
            empty($formData['email']) || empty($formData['password'])) {
            $errors[] = 'Please fill in all required fields.';
        }
        
        if ($formData['password'] !== $formData['confirm_password']) {
            $errors[] = 'Passwords do not match.';
        }
        
        if (!validateEmail($formData['email'])) {
            $errors[] = 'Please enter a valid email address.';
        }
        
        if (!empty($formData['phone']) && !validatePhone($formData['phone'])) {
            $errors[] = 'Please enter a valid phone number.';
        }
        
        if (empty($errors)) {
            // Attempt registration
            $database = new Database();
            $db = $database->getConnection();
            
            if ($db) {
                $user = new User($db);
                $result = $user->register($formData);
                
                if ($result['success']) {
                    setFlashMessage('success', 'Registration successful! Please log in with your credentials.');
                    redirect('login.php');
                } else {
                    $errors = $result['errors'];
                }
            } else {
                $errors[] = 'Database connection failed. Please try again later.';
            }
        }
    }
}

$pageTitle = 'Register - Medical Appointment System';
$hideLayout = true;
?>

<?php require_once '../includes/header.php'; ?>

<div class="container">
    <div class="row justify-content-center min-vh-100 align-items-center py-4">
        <div class="col-md-8 col-lg-6">
            <div class="card">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <i class="fas fa-user-plus fa-3x text-primary mb-3"></i>
                        <h2 class="card-title">Create Account</h2>
                        <p class="text-muted">Join our medical appointment system</p>
                    </div>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" id="registerForm" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="first_name" 
                                       name="first_name" 
                                       value="<?php echo htmlspecialchars($formData['first_name']); ?>" 
                                       required 
                                       autocomplete="given-name">
                                <div class="invalid-feedback">
                                    Please enter your first name.
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="last_name" 
                                       name="last_name" 
                                       value="<?php echo htmlspecialchars($formData['last_name']); ?>" 
                                       required 
                                       autocomplete="family-name">
                                <div class="invalid-feedback">
                                    Please enter your last name.
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address *</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-envelope"></i>
                                </span>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       value="<?php echo htmlspecialchars($formData['email']); ?>" 
                                       required 
                                       autocomplete="email">
                            </div>
                            <div class="invalid-feedback">
                                Please enter a valid email address.
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-phone"></i>
                                </span>
                                <input type="tel" 
                                       class="form-control" 
                                       id="phone" 
                                       name="phone" 
                                       value="<?php echo htmlspecialchars($formData['phone']); ?>" 
                                       autocomplete="tel"
                                       placeholder="e.g., +1234567890">
                            </div>
                            <small class="form-text text-muted">
                                Optional. Include country code (e.g., +1 for US)
                            </small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="role" class="form-label">Account Type</label>
                            <select class="form-select" id="role" name="role">
                                <option value="patient" <?php echo $formData['role'] === 'patient' ? 'selected' : ''; ?>>
                                    Patient - Book appointments
                                </option>
                                <option value="doctor" <?php echo $formData['role'] === 'doctor' ? 'selected' : ''; ?>>
                                    Doctor - Manage appointments and patients
                                </option>
                            </select>
                            <small class="form-text text-muted">
                                Doctor accounts require admin approval before activation.
                            </small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Password *</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" 
                                           class="form-control" 
                                           id="password" 
                                           name="password" 
                                           required 
                                           autocomplete="new-password">
                                    <button class="btn btn-outline-secondary" 
                                            type="button" 
                                            id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback">
                                    Please enter a strong password.
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password *</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" 
                                           class="form-control" 
                                           id="confirm_password" 
                                           name="confirm_password" 
                                           required 
                                           autocomplete="new-password">
                                </div>
                                <div class="invalid-feedback">
                                    Passwords must match.
                                </div>
                            </div>
                        </div>
                        
                        <!-- Password Requirements -->
                        <div class="mb-3">
                            <small class="text-muted">
                                <strong>Password Requirements:</strong><br>
                                • At least 8 characters long<br>
                                • Contains uppercase and lowercase letters<br>
                                • Contains at least one number<br>
                                • Contains at least one special character
                            </small>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the <a href="#" class="text-decoration-none">Terms of Service</a> 
                                and <a href="#" class="text-decoration-none">Privacy Policy</a> *
                            </label>
                            <div class="invalid-feedback">
                                You must agree to the terms and conditions.
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-user-plus me-2"></i>
                                Create Account
                            </button>
                        </div>
                    </form>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <p class="mb-0">
                            Already have an account? 
                            <a href="login.php" class="text-decoration-none">
                                <i class="fas fa-sign-in-alt me-1"></i>
                                Sign in here
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password toggle functionality
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    
    togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        
        const icon = this.querySelector('i');
        icon.classList.toggle('fa-eye');
        icon.classList.toggle('fa-eye-slash');
    });
    
    // Password strength validation
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    
    password.addEventListener('input', function() {
        validatePasswordStrength(this.value);
    });
    
    confirmPassword.addEventListener('input', function() {
        validatePasswordMatch();
    });
    
    function validatePasswordStrength(password) {
        const requirements = {
            length: password.length >= 8,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            number: /[0-9]/.test(password)
        };
        
        const isValid = Object.values(requirements).every(req => req);
        const passwordField = document.getElementById('password');
        
        if (password && !isValid) {
            passwordField.classList.add('is-invalid');
        } else {
            passwordField.classList.remove('is-invalid');
        }
        
        return isValid;
    }
    
    function validatePasswordMatch() {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const confirmField = document.getElementById('confirm_password');
        
        if (confirmPassword && password !== confirmPassword) {
            confirmField.classList.add('is-invalid');
            return false;
        } else {
            confirmField.classList.remove('is-invalid');
            return true;
        }
    }
    
    // Form validation
    const form = document.getElementById('registerForm');
    form.addEventListener('submit', function(e) {
        let isValid = true;
        
        // Validate required fields
        if (!validateForm('registerForm')) {
            isValid = false;
        }
        
        // Validate password strength
        const passwordValue = document.getElementById('password').value;
        if (!validatePasswordStrength(passwordValue)) {
            isValid = false;
        }
        
        // Validate password match
        if (!validatePasswordMatch()) {
            isValid = false;
        }
        
        // Validate email
        const email = document.getElementById('email').value;
        if (email && !isValidEmail(email)) {
            document.getElementById('email').classList.add('is-invalid');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        form.classList.add('was-validated');
    });
    
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>