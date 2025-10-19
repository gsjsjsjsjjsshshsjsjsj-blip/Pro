<?php
/**
 * Role Manager Class
 * Handles role-based access control and permissions
 */

require_once '../config/database.php';
require_once '../includes/helpers.php';

class RoleManager {
    private $conn;
    
    // Define role hierarchy (higher number = more permissions)
    private $roleHierarchy = [
        'patient' => 1,
        'doctor' => 2,
        'admin' => 3
    ];

    // Define permissions for each role
    private $rolePermissions = [
        'patient' => [
            'view_own_appointments',
            'create_appointment',
            'cancel_own_appointment',
            'view_own_profile',
            'edit_own_profile',
            'view_doctors'
        ],
        'doctor' => [
            'view_own_appointments',
            'view_assigned_appointments',
            'update_appointment_status',
            'view_own_profile',
            'edit_own_profile',
            'view_doctor_schedule',
            'manage_availability',
            'view_patient_basic_info'
        ],
        'admin' => [
            'view_all_appointments',
            'create_appointment',
            'update_appointment',
            'delete_appointment',
            'view_all_users',
            'create_user',
            'update_user',
            'deactivate_user',
            'view_all_doctors',
            'manage_doctor_profiles',
            'view_system_reports',
            'manage_system_settings'
        ]
    ];

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Check if user has specific permission
     * @param string $permission
     * @param string $userRole
     * @return bool
     */
    public function hasPermission($permission, $userRole = null) {
        // Use session role if not provided
        if ($userRole === null) {
            $userRole = $_SESSION['user_role'] ?? null;
        }

        if (!$userRole || !isset($this->rolePermissions[$userRole])) {
            return false;
        }

        return in_array($permission, $this->rolePermissions[$userRole]);
    }

    /**
     * Check if user has role or higher
     * @param string $requiredRole
     * @param string $userRole
     * @return bool
     */
    public function hasRoleOrHigher($requiredRole, $userRole = null) {
        // Use session role if not provided
        if ($userRole === null) {
            $userRole = $_SESSION['user_role'] ?? null;
        }

        if (!$userRole || !isset($this->roleHierarchy[$userRole]) || !isset($this->roleHierarchy[$requiredRole])) {
            return false;
        }

        return $this->roleHierarchy[$userRole] >= $this->roleHierarchy[$requiredRole];
    }

    /**
     * Require specific permission or redirect
     * @param string $permission
     * @param string $redirectUrl
     */
    public function requirePermission($permission, $redirectUrl = '../index.php') {
        requireLogin();
        
        if (!$this->hasPermission($permission)) {
            setFlashMessage('error', 'You do not have permission to perform this action.');
            redirect($redirectUrl);
        }
    }

    /**
     * Require specific role or higher
     * @param string $requiredRole
     * @param string $redirectUrl
     */
    public function requireRoleOrHigher($requiredRole, $redirectUrl = '../index.php') {
        requireLogin();
        
        if (!$this->hasRoleOrHigher($requiredRole)) {
            setFlashMessage('error', 'You do not have sufficient privileges to access this page.');
            redirect($redirectUrl);
        }
    }

    /**
     * Get all permissions for a role
     * @param string $role
     * @return array
     */
    public function getRolePermissions($role) {
        return $this->rolePermissions[$role] ?? [];
    }

    /**
     * Get all available roles
     * @return array
     */
    public function getAllRoles() {
        return array_keys($this->roleHierarchy);
    }

    /**
     * Get role display name
     * @param string $role
     * @return string
     */
    public function getRoleDisplayName($role) {
        $displayNames = [
            'patient' => 'Patient',
            'doctor' => 'Doctor',
            'admin' => 'Administrator'
        ];

        return $displayNames[$role] ?? ucfirst($role);
    }

    /**
     * Check if user can access appointment
     * @param int $appointmentId
     * @param int $userId
     * @param string $userRole
     * @return bool
     */
    public function canAccessAppointment($appointmentId, $userId = null, $userRole = null) {
        // Use session data if not provided
        if ($userId === null) {
            $userId = $_SESSION['user_id'] ?? null;
        }
        if ($userRole === null) {
            $userRole = $_SESSION['user_role'] ?? null;
        }

        if (!$userId || !$userRole) {
            return false;
        }

        // Admin can access all appointments
        if ($userRole === 'admin') {
            return true;
        }

        try {
            if ($userRole === 'patient') {
                // Patient can only access their own appointments
                $query = "SELECT id FROM appointments WHERE id = :appointment_id AND patient_id = :user_id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':appointment_id', $appointmentId);
                $stmt->bindParam(':user_id', $userId);
                $stmt->execute();
                return $stmt->rowCount() > 0;
            }

            if ($userRole === 'doctor') {
                // Doctor can access appointments assigned to them
                $query = "SELECT a.id FROM appointments a 
                         INNER JOIN doctors d ON a.doctor_id = d.id 
                         WHERE a.id = :appointment_id AND d.user_id = :user_id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':appointment_id', $appointmentId);
                $stmt->bindParam(':user_id', $userId);
                $stmt->execute();
                return $stmt->rowCount() > 0;
            }

            return false;

        } catch (PDOException $e) {
            error_log("Error checking appointment access: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user can access another user's profile
     * @param int $targetUserId
     * @param int $currentUserId
     * @param string $currentUserRole
     * @return bool
     */
    public function canAccessUserProfile($targetUserId, $currentUserId = null, $currentUserRole = null) {
        // Use session data if not provided
        if ($currentUserId === null) {
            $currentUserId = $_SESSION['user_id'] ?? null;
        }
        if ($currentUserRole === null) {
            $currentUserRole = $_SESSION['user_role'] ?? null;
        }

        if (!$currentUserId || !$currentUserRole) {
            return false;
        }

        // Users can always access their own profile
        if ($targetUserId == $currentUserId) {
            return true;
        }

        // Admin can access all profiles
        if ($currentUserRole === 'admin') {
            return true;
        }

        // Doctors can access basic patient info for their appointments
        if ($currentUserRole === 'doctor') {
            try {
                $query = "SELECT DISTINCT a.patient_id FROM appointments a 
                         INNER JOIN doctors d ON a.doctor_id = d.id 
                         WHERE d.user_id = :doctor_user_id AND a.patient_id = :patient_id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':doctor_user_id', $currentUserId);
                $stmt->bindParam(':patient_id', $targetUserId);
                $stmt->execute();
                return $stmt->rowCount() > 0;
            } catch (PDOException $e) {
                error_log("Error checking profile access: " . $e->getMessage());
                return false;
            }
        }

        return false;
    }

    /**
     * Get user's role-based dashboard URL
     * @param string $role
     * @return string
     */
    public function getDashboardUrl($role) {
        $dashboardUrls = [
            'patient' => '../patient/dashboard.php',
            'doctor' => '../doctor/dashboard.php',
            'admin' => '../admin/dashboard.php'
        ];

        return $dashboardUrls[$role] ?? '../index.php';
    }

    /**
     * Get navigation menu items based on role
     * @param string $role
     * @return array
     */
    public function getNavigationMenu($role) {
        $menus = [
            'patient' => [
                ['label' => 'Dashboard', 'url' => '../patient/dashboard.php', 'icon' => 'fas fa-tachometer-alt'],
                ['label' => 'Book Appointment', 'url' => '../patient/book-appointment.php', 'icon' => 'fas fa-calendar-plus'],
                ['label' => 'My Appointments', 'url' => '../patient/appointments.php', 'icon' => 'fas fa-calendar-check'],
                ['label' => 'Doctors', 'url' => '../patient/doctors.php', 'icon' => 'fas fa-user-md'],
                ['label' => 'Profile', 'url' => '../patient/profile.php', 'icon' => 'fas fa-user']
            ],
            'doctor' => [
                ['label' => 'Dashboard', 'url' => '../doctor/dashboard.php', 'icon' => 'fas fa-tachometer-alt'],
                ['label' => 'My Schedule', 'url' => '../doctor/schedule.php', 'icon' => 'fas fa-calendar'],
                ['label' => 'Appointments', 'url' => '../doctor/appointments.php', 'icon' => 'fas fa-calendar-check'],
                ['label' => 'Availability', 'url' => '../doctor/availability.php', 'icon' => 'fas fa-clock'],
                ['label' => 'Profile', 'url' => '../doctor/profile.php', 'icon' => 'fas fa-user']
            ],
            'admin' => [
                ['label' => 'Dashboard', 'url' => '../admin/dashboard.php', 'icon' => 'fas fa-tachometer-alt'],
                ['label' => 'Users', 'url' => '../admin/users.php', 'icon' => 'fas fa-users'],
                ['label' => 'Doctors', 'url' => '../admin/doctors.php', 'icon' => 'fas fa-user-md'],
                ['label' => 'Appointments', 'url' => '../admin/appointments.php', 'icon' => 'fas fa-calendar-check'],
                ['label' => 'Reports', 'url' => '../admin/reports.php', 'icon' => 'fas fa-chart-bar'],
                ['label' => 'Settings', 'url' => '../admin/settings.php', 'icon' => 'fas fa-cog']
            ]
        ];

        return $menus[$role] ?? [];
    }

    /**
     * Log role-based action
     * @param string $action
     * @param string $resource
     * @param int $resourceId
     */
    public function logRoleAction($action, $resource, $resourceId = null) {
        $userId = $_SESSION['user_id'] ?? 'anonymous';
        $userRole = $_SESSION['user_role'] ?? 'unknown';
        $details = "Role: $userRole, Action: $action, Resource: $resource";
        
        if ($resourceId) {
            $details .= ", Resource ID: $resourceId";
        }

        logActivity($action, $details);
    }

    /**
     * Validate role assignment
     * @param string $role
     * @param string $assignerRole
     * @return bool
     */
    public function canAssignRole($role, $assignerRole = null) {
        if ($assignerRole === null) {
            $assignerRole = $_SESSION['user_role'] ?? null;
        }

        // Only admins can assign roles
        if ($assignerRole !== 'admin') {
            return false;
        }

        // Check if role exists
        return isset($this->roleHierarchy[$role]);
    }

    /**
     * Get filtered appointment query based on role
     * @param string $baseQuery
     * @param string $role
     * @param int $userId
     * @return string
     */
    public function getFilteredAppointmentQuery($baseQuery, $role = null, $userId = null) {
        if ($role === null) {
            $role = $_SESSION['user_role'] ?? null;
        }
        if ($userId === null) {
            $userId = $_SESSION['user_id'] ?? null;
        }

        switch ($role) {
            case 'patient':
                return $baseQuery . " AND a.patient_id = $userId";
            
            case 'doctor':
                return $baseQuery . " AND d.user_id = $userId";
            
            case 'admin':
                return $baseQuery; // No additional filter for admin
            
            default:
                return $baseQuery . " AND 1=0"; // No results for unknown roles
        }
    }
}
?>