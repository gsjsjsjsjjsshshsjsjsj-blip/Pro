<?php
/**
 * Authentication Controller
 * Handles user authentication logic
 */

class AuthController {
    private $db;
    private $user;
    
    public function __construct($database) {
        $this->db = $database;
        $this->user = new User($this->db);
    }
    
    /**
     * Handle user login
     */
    public function login($email, $password) {
        try {
            $userData = $this->user->authenticate($email, $password);
            
            if ($userData) {
                // Start session and set user data
                session_regenerate_id(true);
                $_SESSION['user_id'] = $userData['id'];
                $_SESSION['user_email'] = $userData['email'];
                $_SESSION['user_name'] = $userData['first_name'] . ' ' . $userData['last_name'];
                $_SESSION['user_role'] = $userData['role'];
                $_SESSION['is_logged_in'] = true;
                
                return [
                    'success' => true,
                    'message' => 'Login successful',
                    'redirect' => $this->getDashboardUrl($userData['role'])
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Invalid email or password'
                ];
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred during login'
            ];
        }
    }
    
    /**
     * Handle user registration
     */
    public function register($userData) {
        try {
            // Validate input data
            $validation = $this->validateRegistrationData($userData);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => $validation['message']
                ];
            }
            
            // Check if user already exists
            if ($this->user->emailExists($userData['email'])) {
                return [
                    'success' => false,
                    'message' => 'Email address is already registered'
                ];
            }
            
            // Create new user
            $userId = $this->user->create($userData);
            
            if ($userId) {
                return [
                    'success' => true,
                    'message' => 'Registration successful. Please login.',
                    'redirect' => '/auth/login.php'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Registration failed. Please try again.'
                ];
            }
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred during registration'
            ];
        }
    }
    
    /**
     * Handle user logout
     */
    public function logout() {
        // Destroy session
        session_destroy();
        
        return [
            'success' => true,
            'message' => 'Logged out successfully',
            'redirect' => '/index.php'
        ];
    }
    
    /**
     * Validate registration data
     */
    private function validateRegistrationData($data) {
        $required = ['first_name', 'last_name', 'email', 'password', 'role'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return [
                    'valid' => false,
                    'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required'
                ];
            }
        }
        
        // Email validation
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return [
                'valid' => false,
                'message' => 'Invalid email format'
            ];
        }
        
        // Password validation
        if (strlen($data['password']) < 6) {
            return [
                'valid' => false,
                'message' => 'Password must be at least 6 characters long'
            ];
        }
        
        // Role validation
        $validRoles = ['patient', 'doctor', 'admin'];
        if (!in_array($data['role'], $validRoles)) {
            return [
                'valid' => false,
                'message' => 'Invalid role specified'
            ];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Get dashboard URL based on user role
     */
    private function getDashboardUrl($role) {
        switch ($role) {
            case 'admin':
                return '/frontend/pages/admin/dashboard.php';
            case 'doctor':
                return '/frontend/pages/doctor/dashboard.php';
            case 'patient':
                return '/frontend/pages/patient/dashboard.php';
            default:
                return '/index.php';
        }
    }
}