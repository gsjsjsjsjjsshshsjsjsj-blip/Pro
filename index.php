<?php
/**
 * Main Landing Page
 * Welcome page with system overview and quick access
 */

require_once 'includes/config.php';

// Redirect logged-in users to their dashboard
if (isLoggedIn()) {
    $database = new Database();
    $db = $database->getConnection();
    if ($db) {
        $roleManager = new RoleManager($db);
        $dashboardUrl = $roleManager->getDashboardUrl($_SESSION['user_role']);
        redirect($dashboardUrl);
    }
}

$pageTitle = 'Medical Appointment System - Professional Healthcare Management';
$hideLayout = true;
?>

<?php require_once 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="hero-section bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center min-vh-75">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4">
                    Professional Medical Appointment Management
                </h1>
                <p class="lead mb-4">
                    Streamline your healthcare practice with our secure, user-friendly appointment booking system. 
                    Designed for patients, doctors, and healthcare administrators.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="auth/register.php" class="btn btn-light btn-lg">
                        <i class="fas fa-user-plus me-2"></i>
                        Get Started
                    </a>
                    <a href="auth/login.php" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-sign-in-alt me-2"></i>
                        Sign In
                    </a>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <i class="fas fa-heartbeat fa-10x opacity-75"></i>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section py-5">
    <div class="container">
        <div class="row text-center mb-5">
            <div class="col-12">
                <h2 class="display-5 fw-bold mb-3">Why Choose Our System?</h2>
                <p class="lead text-muted">
                    Built with modern web technologies and best practices for security, usability, and performance.
                </p>
            </div>
        </div>
        
        <div class="row g-4">
            <!-- For Patients -->
            <div class="col-lg-4">
                <div class="card h-100 text-center">
                    <div class="card-body p-4">
                        <div class="feature-icon text-primary mb-3">
                            <i class="fas fa-user-injured fa-3x"></i>
                        </div>
                        <h4 class="card-title">For Patients</h4>
                        <p class="card-text">
                            Easy appointment booking, view your medical history, 
                            manage upcoming appointments, and find the right doctors for your needs.
                        </p>
                        <ul class="list-unstyled text-start">
                            <li><i class="fas fa-check text-success me-2"></i>Quick appointment booking</li>
                            <li><i class="fas fa-check text-success me-2"></i>View appointment history</li>
                            <li><i class="fas fa-check text-success me-2"></i>Doctor search and profiles</li>
                            <li><i class="fas fa-check text-success me-2"></i>Appointment reminders</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- For Doctors -->
            <div class="col-lg-4">
                <div class="card h-100 text-center">
                    <div class="card-body p-4">
                        <div class="feature-icon text-primary mb-3">
                            <i class="fas fa-user-md fa-3x"></i>
                        </div>
                        <h4 class="card-title">For Doctors</h4>
                        <p class="card-text">
                            Manage your schedule efficiently, track patient appointments, 
                            update availability, and maintain professional profiles.
                        </p>
                        <ul class="list-unstyled text-start">
                            <li><i class="fas fa-check text-success me-2"></i>Schedule management</li>
                            <li><i class="fas fa-check text-success me-2"></i>Patient appointment tracking</li>
                            <li><i class="fas fa-check text-success me-2"></i>Availability control</li>
                            <li><i class="fas fa-check text-success me-2"></i>Professional profiles</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- For Administrators -->
            <div class="col-lg-4">
                <div class="card h-100 text-center">
                    <div class="card-body p-4">
                        <div class="feature-icon text-primary mb-3">
                            <i class="fas fa-cogs fa-3x"></i>
                        </div>
                        <h4 class="card-title">For Administrators</h4>
                        <p class="card-text">
                            Complete system oversight with user management, 
                            comprehensive reporting, and system configuration tools.
                        </p>
                        <ul class="list-unstyled text-start">
                            <li><i class="fas fa-check text-success me-2"></i>User management</li>
                            <li><i class="fas fa-check text-success me-2"></i>System reports</li>
                            <li><i class="fas fa-check text-success me-2"></i>Doctor approval workflow</li>
                            <li><i class="fas fa-check text-success me-2"></i>System configuration</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Security & Technology Section -->
<section class="technology-section bg-light py-5">
    <div class="container">
        <div class="row text-center mb-5">
            <div class="col-12">
                <h2 class="display-5 fw-bold mb-3">Built with Best Practices</h2>
                <p class="lead text-muted">
                    Security, performance, and user experience are our top priorities.
                </p>
            </div>
        </div>
        
        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="text-center">
                    <div class="feature-icon text-primary mb-3">
                        <i class="fas fa-shield-alt fa-2x"></i>
                    </div>
                    <h5>Secure</h5>
                    <p class="text-muted">
                        Password hashing, CSRF protection, SQL injection prevention, 
                        and secure session management.
                    </p>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3">
                <div class="text-center">
                    <div class="feature-icon text-primary mb-3">
                        <i class="fas fa-mobile-alt fa-2x"></i>
                    </div>
                    <h5>Responsive</h5>
                    <p class="text-muted">
                        Bootstrap-powered responsive design that works perfectly 
                        on desktop, tablet, and mobile devices.
                    </p>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3">
                <div class="text-center">
                    <div class="feature-icon text-primary mb-3">
                        <i class="fas fa-users-cog fa-2x"></i>
                    </div>
                    <h5>Role-Based</h5>
                    <p class="text-muted">
                        Comprehensive role-based access control with proper 
                        permissions for patients, doctors, and administrators.
                    </p>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3">
                <div class="text-center">
                    <div class="feature-icon text-primary mb-3">
                        <i class="fas fa-database fa-2x"></i>
                    </div>
                    <h5>Reliable</h5>
                    <p class="text-muted">
                        MySQL database with proper indexing, prepared statements, 
                        and optimized queries for performance.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Demo Section -->
<section class="demo-section py-5">
    <div class="container">
        <div class="row text-center mb-5">
            <div class="col-12">
                <h2 class="display-5 fw-bold mb-3">Try the Demo</h2>
                <p class="lead text-muted">
                    Experience the system with our pre-configured demo accounts.
                </p>
            </div>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body p-4">
                        <h5 class="card-title text-center mb-4">
                            <i class="fas fa-play-circle me-2"></i>
                            Demo Accounts
                        </h5>
                        
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="demo-account text-center p-3 border rounded">
                                    <i class="fas fa-user-shield fa-2x text-primary mb-2"></i>
                                    <h6>Administrator</h6>
                                    <small class="text-muted">
                                        <strong>Email:</strong> admin@medical.local<br>
                                        <strong>Password:</strong> admin123
                                    </small>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="demo-account text-center p-3 border rounded">
                                    <i class="fas fa-user-md fa-2x text-success mb-2"></i>
                                    <h6>Doctor</h6>
                                    <small class="text-muted">
                                        <strong>Email:</strong> dr.smith@medical.local<br>
                                        <strong>Password:</strong> admin123
                                    </small>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="demo-account text-center p-3 border rounded">
                                    <i class="fas fa-user fa-2x text-info mb-2"></i>
                                    <h6>Patient</h6>
                                    <small class="text-muted">
                                        Register a new patient account<br>
                                        to test the booking system
                                    </small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <a href="auth/login.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Try Demo Now
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.hero-section {
    background: linear-gradient(135deg, var(--primary-color) 0%, #1e3d6f 100%);
    min-height: 80vh;
}

.min-vh-75 {
    min-height: 75vh;
}

.fa-10x {
    font-size: 10rem;
}

.demo-account {
    transition: transform 0.2s, box-shadow 0.2s;
}

.demo-account:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.feature-icon {
    transition: transform 0.3s ease;
}

.card:hover .feature-icon {
    transform: scale(1.1);
}
</style>

<?php require_once 'includes/footer.php'; ?>