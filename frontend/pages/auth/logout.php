<?php
/**
 * Logout Script
 * Secure user logout with session cleanup
 */

require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../classes/User.php';

// Only process logout if user is logged in
if (isLoggedIn()) {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        $user = new User($db);
        $user->logout();
    } else {
        // Fallback: destroy session even if database is unavailable
        session_destroy();
    }
    
    setFlashMessage('success', 'You have been successfully logged out.');
}

// Redirect to login page
redirect('login.php');
?>