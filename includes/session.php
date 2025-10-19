<?php
/**
 * Session Management
 * Secure session handling and initialization
 */

// Start session with secure settings
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session parameters
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Strict');
    
    // Set session name
    session_name('MEDICAL_APPOINTMENT_SESSION');
    
    // Start session
    session_start();
    
    // Regenerate session ID periodically for security
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

// Include helper functions
require_once 'helpers.php';

// Clean expired sessions periodically
if (rand(1, 100) <= 5) { // 5% chance
    require_once '../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    if ($db) {
        cleanExpiredSessions($db);
    }
}

// Validate session token if user is logged in
if (isLoggedIn() && isset($_SESSION['session_token'])) {
    require_once '../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        try {
            $query = "SELECT expires_at FROM user_sessions 
                     WHERE session_token = :token AND user_id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':token', $_SESSION['session_token']);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->execute();
            
            if ($stmt->rowCount() == 0) {
                // Invalid session token
                session_destroy();
                setFlashMessage('error', 'Your session has expired. Please log in again.');
                redirect('../auth/login.php');
            } else {
                $session = $stmt->fetch(PDO::FETCH_ASSOC);
                if (strtotime($session['expires_at']) < time()) {
                    // Expired session
                    session_destroy();
                    setFlashMessage('error', 'Your session has expired. Please log in again.');
                    redirect('../auth/login.php');
                }
            }
        } catch (PDOException $e) {
            error_log("Session validation error: " . $e->getMessage());
        }
    }
}
?>