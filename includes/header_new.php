<?php
/**
 * Header Template
 * Shared header component with Bootstrap and responsive navigation
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    require_once 'config.php';
}

// Get current user info
$isLoggedIn = isLoggedIn();
$userName = $_SESSION['user_name'] ?? '';
$userRole = $_SESSION['user_role'] ?? '';

// Get navigation menu if user is logged in
$navigationMenu = [];
if ($isLoggedIn) {
    $roleManager = new RoleManager($db);
    $navigationMenu = $roleManager->getNavigationMenu($userRole);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Medical Appointment Booking System - Book appointments with healthcare professionals">
    <meta name="author" content="Medical Appointment System">
    
    <!-- Security Headers -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    
    <title><?php echo $pageTitle ?? APP_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo FRONTEND_URL; ?>css/styles.css" rel="stylesheet">
    
    <?php if (isset($additionalCSS)): ?>
        <?php echo $additionalCSS; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>index.php">
                <i class="fas fa-heartbeat me-2"></i>
                <?php echo APP_NAME; ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <?php if ($isLoggedIn): ?>
                    <!-- Logged in navigation -->
                    <ul class="navbar-nav me-auto">
                        <?php foreach ($navigationMenu as $item): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo htmlspecialchars($item['url']); ?>">
                                    <i class="<?php echo htmlspecialchars($item['icon']); ?> me-1"></i>
                                    <?php echo htmlspecialchars($item['label']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <ul class="navbar-nav">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i>
                                <?php echo htmlspecialchars($userName); ?>
                                <span class="badge bg-secondary ms-1"><?php echo ucfirst($userRole); ?></span>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="<?php echo FRONTEND_URL; ?>pages/<?php echo $userRole; ?>/profile.php">
                                    <i class="fas fa-user me-2"></i>Profile
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo FRONTEND_URL; ?>pages/auth/logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a></li>
                            </ul>
                        </li>
                    </ul>
                <?php else: ?>
                    <!-- Guest navigation -->
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo FRONTEND_URL; ?>pages/auth/login.php">
                                <i class="fas fa-sign-in-alt me-1"></i>Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo FRONTEND_URL; ?>pages/auth/register.php">
                                <i class="fas fa-user-plus me-1"></i>Register
                            </a>
                        </li>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <!-- Flash Messages -->
    <?php 
    $flashMessages = getFlashMessages();
    if (!empty($flashMessages)): 
    ?>
        <div class="container-fluid mt-3">
            <?php foreach ($flashMessages as $message): ?>
                <div class="alert alert-<?php echo $message['type'] === 'error' ? 'danger' : $message['type']; ?> alert-dismissible fade show" role="alert">
                    <i class="fas fa-<?php 
                        echo $message['type'] === 'success' ? 'check-circle' : 
                            ($message['type'] === 'error' ? 'exclamation-triangle' : 
                            ($message['type'] === 'warning' ? 'exclamation-circle' : 'info-circle')); 
                    ?> me-2"></i>
                    <?php echo htmlspecialchars($message['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <!-- Main Content Container -->
    <div class="container-fluid">
        <?php if ($isLoggedIn && !isset($hideLayout)): ?>
            <div class="row">
                <!-- Sidebar -->
                <div class="col-md-3 col-lg-2 px-0">
                    <div class="sidebar">
                        <nav class="nav flex-column">
                            <?php foreach ($navigationMenu as $item): ?>
                                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) === basename($item['url'])) ? 'active' : ''; ?>" 
                                   href="<?php echo htmlspecialchars($item['url']); ?>">
                                    <i class="<?php echo htmlspecialchars($item['icon']); ?> me-2"></i>
                                    <?php echo htmlspecialchars($item['label']); ?>
                                </a>
                            <?php endforeach; ?>
                        </nav>
                    </div>
                </div>
                
                <!-- Main Content -->
                <div class="col-md-9 col-lg-10">
                    <div class="main-content">
        <?php else: ?>
            <div class="main-content">
        <?php endif; ?>