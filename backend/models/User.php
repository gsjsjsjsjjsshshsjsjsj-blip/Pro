<?php
/**
 * User Class
 * Handles user authentication, registration, and user management
 */

require_once '../config/database.php';
require_once '../includes/helpers.php';

class User {
    private $conn;
    private $table_name = "users";
    private $sessions_table = "user_sessions";

    public $id;
    public $first_name;
    public $last_name;
    public $email;
    public $phone;
    public $password_hash;
    public $role;
    public $is_active;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Register a new user
     * @param array $userData
     * @return array
     */
    public function register($userData) {
        try {
            // Validate input data
            $validation = $this->validateRegistrationData($userData);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'errors' => $validation['errors']
                ];
            }

            // Check if email already exists
            if ($this->emailExists($userData['email'])) {
                return [
                    'success' => false,
                    'errors' => ['Email address is already registered']
                ];
            }

            // Prepare SQL query
            $query = "INSERT INTO " . $this->table_name . " 
                     (first_name, last_name, email, phone, password_hash, role) 
                     VALUES (:first_name, :last_name, :email, :phone, :password_hash, :role)";

            $stmt = $this->conn->prepare($query);

            // Hash password
            $password_hash = hashPassword($userData['password']);

            // Bind parameters
            $stmt->bindParam(':first_name', $userData['first_name']);
            $stmt->bindParam(':last_name', $userData['last_name']);
            $stmt->bindParam(':email', $userData['email']);
            $stmt->bindParam(':phone', $userData['phone']);
            $stmt->bindParam(':password_hash', $password_hash);
            $stmt->bindParam(':role', $userData['role']);

            if ($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                logActivity('user_registered', "New user registered: " . $userData['email']);
                
                return [
                    'success' => true,
                    'user_id' => $this->id,
                    'message' => 'Registration successful'
                ];
            }

            return [
                'success' => false,
                'errors' => ['Registration failed. Please try again.']
            ];

        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            return [
                'success' => false,
                'errors' => ['Registration failed. Please try again.']
            ];
        }
    }

    /**
     * Login user
     * @param string $email
     * @param string $password
     * @return array
     */
    public function login($email, $password) {
        try {
            // Validate input
            if (empty($email) || empty($password)) {
                return [
                    'success' => false,
                    'errors' => ['Email and password are required']
                ];
            }

            if (!validateEmail($email)) {
                return [
                    'success' => false,
                    'errors' => ['Invalid email format']
                ];
            }

            // Get user by email
            $query = "SELECT id, first_name, last_name, email, password_hash, role, is_active 
                     FROM " . $this->table_name . " 
                     WHERE email = :email AND is_active = 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            if ($stmt->rowCount() == 1) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                // Verify password
                if (verifyPassword($password, $row['password_hash'])) {
                    // Set user properties
                    $this->id = $row['id'];
                    $this->first_name = $row['first_name'];
                    $this->last_name = $row['last_name'];
                    $this->email = $row['email'];
                    $this->role = $row['role'];

                    // Create session
                    $sessionToken = $this->createSession($this->id);

                    // Set session variables
                    $_SESSION['user_id'] = $this->id;
                    $_SESSION['user_name'] = $this->first_name . ' ' . $this->last_name;
                    $_SESSION['user_email'] = $this->email;
                    $_SESSION['user_role'] = $this->role;
                    $_SESSION['session_token'] = $sessionToken;

                    logActivity('user_login', "User logged in: " . $email);

                    return [
                        'success' => true,
                        'user' => [
                            'id' => $this->id,
                            'name' => $this->first_name . ' ' . $this->last_name,
                            'email' => $this->email,
                            'role' => $this->role
                        ],
                        'message' => 'Login successful'
                    ];
                }
            }

            return [
                'success' => false,
                'errors' => ['Invalid email or password']
            ];

        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return [
                'success' => false,
                'errors' => ['Login failed. Please try again.']
            ];
        }
    }

    /**
     * Logout user
     * @return bool
     */
    public function logout() {
        try {
            // Remove session from database
            if (isset($_SESSION['session_token'])) {
                $this->removeSession($_SESSION['session_token']);
            }

            logActivity('user_logout', "User logged out");

            // Destroy session
            session_destroy();
            return true;

        } catch (Exception $e) {
            error_log("Logout error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user by ID
     * @param int $id
     * @return array|false
     */
    public function getUserById($id) {
        try {
            $query = "SELECT id, first_name, last_name, email, phone, role, is_active, created_at 
                     FROM " . $this->table_name . " 
                     WHERE id = :id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            if ($stmt->rowCount() == 1) {
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }

            return false;

        } catch (PDOException $e) {
            error_log("Get user error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update user profile
     * @param int $id
     * @param array $userData
     * @return array
     */
    public function updateProfile($id, $userData) {
        try {
            // Validate input data
            $validation = $this->validateProfileData($userData);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'errors' => $validation['errors']
                ];
            }

            // Check if email exists for other users
            if ($this->emailExistsForOtherUser($userData['email'], $id)) {
                return [
                    'success' => false,
                    'errors' => ['Email address is already in use by another user']
                ];
            }

            $query = "UPDATE " . $this->table_name . " 
                     SET first_name = :first_name, last_name = :last_name, 
                         email = :email, phone = :phone, updated_at = NOW()
                     WHERE id = :id";

            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(':first_name', $userData['first_name']);
            $stmt->bindParam(':last_name', $userData['last_name']);
            $stmt->bindParam(':email', $userData['email']);
            $stmt->bindParam(':phone', $userData['phone']);
            $stmt->bindParam(':id', $id);

            if ($stmt->execute()) {
                logActivity('profile_updated', "Profile updated for user ID: " . $id);
                return [
                    'success' => true,
                    'message' => 'Profile updated successfully'
                ];
            }

            return [
                'success' => false,
                'errors' => ['Profile update failed. Please try again.']
            ];

        } catch (PDOException $e) {
            error_log("Profile update error: " . $e->getMessage());
            return [
                'success' => false,
                'errors' => ['Profile update failed. Please try again.']
            ];
        }
    }

    /**
     * Change user password
     * @param int $id
     * @param string $currentPassword
     * @param string $newPassword
     * @return array
     */
    public function changePassword($id, $currentPassword, $newPassword) {
        try {
            // Get current password hash
            $query = "SELECT password_hash FROM " . $this->table_name . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            if ($stmt->rowCount() != 1) {
                return [
                    'success' => false,
                    'errors' => ['User not found']
                ];
            }

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verify current password
            if (!verifyPassword($currentPassword, $row['password_hash'])) {
                return [
                    'success' => false,
                    'errors' => ['Current password is incorrect']
                ];
            }

            // Validate new password
            $passwordValidation = validatePassword($newPassword);
            if (!$passwordValidation['valid']) {
                return [
                    'success' => false,
                    'errors' => $passwordValidation['errors']
                ];
            }

            // Update password
            $newPasswordHash = hashPassword($newPassword);
            $updateQuery = "UPDATE " . $this->table_name . " 
                           SET password_hash = :password_hash, updated_at = NOW() 
                           WHERE id = :id";

            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bindParam(':password_hash', $newPasswordHash);
            $updateStmt->bindParam(':id', $id);

            if ($updateStmt->execute()) {
                logActivity('password_changed', "Password changed for user ID: " . $id);
                return [
                    'success' => true,
                    'message' => 'Password changed successfully'
                ];
            }

            return [
                'success' => false,
                'errors' => ['Password change failed. Please try again.']
            ];

        } catch (PDOException $e) {
            error_log("Password change error: " . $e->getMessage());
            return [
                'success' => false,
                'errors' => ['Password change failed. Please try again.']
            ];
        }
    }

    /**
     * Create user session
     * @param int $userId
     * @return string
     */
    private function createSession($userId) {
        try {
            $sessionToken = generateSecureToken();
            $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

            $query = "INSERT INTO " . $this->sessions_table . " 
                     (user_id, session_token, expires_at) 
                     VALUES (:user_id, :session_token, :expires_at)";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':session_token', $sessionToken);
            $stmt->bindParam(':expires_at', $expiresAt);
            $stmt->execute();

            return $sessionToken;

        } catch (PDOException $e) {
            error_log("Create session error: " . $e->getMessage());
            return generateSecureToken(); // Fallback token
        }
    }

    /**
     * Remove user session
     * @param string $sessionToken
     * @return bool
     */
    private function removeSession($sessionToken) {
        try {
            $query = "DELETE FROM " . $this->sessions_table . " WHERE session_token = :session_token";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':session_token', $sessionToken);
            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Remove session error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if email exists
     * @param string $email
     * @return bool
     */
    private function emailExists($email) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /**
     * Check if email exists for other user
     * @param string $email
     * @param int $userId
     * @return bool
     */
    private function emailExistsForOtherUser($email, $userId) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email AND id != :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /**
     * Validate registration data
     * @param array $data
     * @return array
     */
    private function validateRegistrationData($data) {
        $errors = [];

        if (empty($data['first_name'])) {
            $errors[] = "First name is required";
        }

        if (empty($data['last_name'])) {
            $errors[] = "Last name is required";
        }

        if (empty($data['email'])) {
            $errors[] = "Email is required";
        } elseif (!validateEmail($data['email'])) {
            $errors[] = "Invalid email format";
        }

        if (!empty($data['phone']) && !validatePhone($data['phone'])) {
            $errors[] = "Invalid phone number format";
        }

        if (empty($data['password'])) {
            $errors[] = "Password is required";
        } else {
            $passwordValidation = validatePassword($data['password']);
            if (!$passwordValidation['valid']) {
                $errors = array_merge($errors, $passwordValidation['errors']);
            }
        }

        if (!in_array($data['role'], ['patient', 'doctor', 'admin'])) {
            $data['role'] = 'patient'; // Default role
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate profile data
     * @param array $data
     * @return array
     */
    private function validateProfileData($data) {
        $errors = [];

        if (empty($data['first_name'])) {
            $errors[] = "First name is required";
        }

        if (empty($data['last_name'])) {
            $errors[] = "Last name is required";
        }

        if (empty($data['email'])) {
            $errors[] = "Email is required";
        } elseif (!validateEmail($data['email'])) {
            $errors[] = "Invalid email format";
        }

        if (!empty($data['phone']) && !validatePhone($data['phone'])) {
            $errors[] = "Invalid phone number format";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
?>