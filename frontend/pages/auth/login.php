<?php
/**
 * Login Page
 * User authentication with secure session management
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
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $errors[] = 'Please fill in all required fields.';
        } else {
            // Attempt login
            $database = new Database();
            $db = $database->getConnection();
            
            if ($db) {
                $user = new User($db);
                $result = $user->login($email, $password);
                
                if ($result['success']) {
                    // Redirect to appropriate dashboard
                    $roleManager = new RoleManager($db);
                    $dashboardUrl = $roleManager->getDashboardUrl($result['user']['role']);
                    
                    setFlashMessage('success', 'Welcome back, ' . $result['user']['name'] . '!');
                    redirect($dashboardUrl);
                } else {
                    $errors = $result['errors'];
                }
            } else {
                $errors[] = 'Database connection failed. Please try again later.';
            }
        }
    }
}

$pageTitle = 'Login - Medical Appointment System';
$hideLayout = true;
?>

<?php require_once '../includes/header.php'; ?>

<div class="container">
    <div class="row justify-content-center min-vh-100 align-items-center">
        <div class="col-md-6 col-lg-4">
            <div class="card">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <i class="fas fa-heartbeat fa-3x text-primary mb-3"></i>
                        <h2 class="card-title">Welcome Back</h2>
                        <p class="text-muted">Sign in to your account</p>
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
                    
                    <form method="POST" id="loginForm" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-envelope"></i>
                                </span>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       value="<?php echo htmlspecialchars($email); ?>" 
                                       required 
                                       autocomplete="email">
                            </div>
                            <div class="invalid-feedback">
                                Please enter a valid email address.
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" 
                                       class="form-control" 
                                       id="password" 
                                       name="password" 
                                       required 
                                       autocomplete="current-password">
                                <button class="btn btn-outline-secondary" 
                                        type="button" 
                                        id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback">
                                Please enter your password.
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="rememberMe" name="remember_me">
                            <label class="form-check-label" for="rememberMe">
                                Remember me
                            </label>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Sign In
                            </button>
                        </div>
                    </form>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <p class="mb-2">
                            <a href="forgot-password.php" class="text-decoration-none">
                                <i class="fas fa-key me-1"></i>
                                Forgot your password?
                            </a>
                        </p>
                        <p class="mb-0">
                            Don't have an account? 
                            <a href="register.php" class="text-decoration-none">
                                <i class="fas fa-user-plus me-1"></i>
                                Sign up here
                            </a>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Demo Accounts Info -->
            <div class="card mt-3">
                <div class="card-body p-3">
                    <h6 class="card-title">
                        <i class="fas fa-info-circle me-2"></i>
                        Demo Accounts
                    </h6>
                    <small class="text-muted">
                        <strong>Admin:</strong> admin@medical.local / admin123<br>
                        <strong>Doctor:</strong> dr.smith@medical.local / admin123<br>
                        <strong>Patient:</strong> Register a new account
                    </small>
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
    
    // Form validation
    const form = document.getElementById('loginForm');
    form.addEventListener('submit', function(e) {
        if (!validateForm('loginForm')) {
            e.preventDefault();
            e.stopPropagation();
        }
        form.classList.add('was-validated');
    });
    
    // Email validation
    const emailInput = document.getElementById('email');
    emailInput.addEventListener('blur', function() {
        const email = this.value.trim();
        if (email && !isValidEmail(email)) {
            this.classList.add('is-invalid');
        } else {
            this.classList.remove('is-invalid');
        }
    });
    
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>