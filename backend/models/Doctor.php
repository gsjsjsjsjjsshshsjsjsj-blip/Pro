<?php
/**
 * Doctor Class
 * Handles doctor-specific operations and profile management
 */

require_once '../config/database.php';
require_once '../includes/helpers.php';

class Doctor {
    private $conn;
    private $table_name = "doctors";
    private $users_table = "users";

    public $id;
    public $user_id;
    public $specialization;
    public $license_number;
    public $consultation_fee;
    public $experience_years;
    public $bio;
    public $available_days;
    public $working_hours_start;
    public $working_hours_end;
    public $is_available;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Create doctor profile
     * @param array $doctorData
     * @return array
     */
    public function create($doctorData) {
        try {
            // Validate input data
            $validation = $this->validateDoctorData($doctorData);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'errors' => $validation['errors']
                ];
            }

            // Check if license number already exists
            if ($this->licenseExists($doctorData['license_number'])) {
                return [
                    'success' => false,
                    'errors' => ['License number is already registered']
                ];
            }

            $query = "INSERT INTO " . $this->table_name . " 
                     (user_id, specialization, license_number, consultation_fee, experience_years, bio, available_days, working_hours_start, working_hours_end) 
                     VALUES (:user_id, :specialization, :license_number, :consultation_fee, :experience_years, :bio, :available_days, :working_hours_start, :working_hours_end)";

            $stmt = $this->conn->prepare($query);

            // Set default values
            $availableDays = $doctorData['available_days'] ?? '["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"]';
            $workingHoursStart = $doctorData['working_hours_start'] ?? '09:00:00';
            $workingHoursEnd = $doctorData['working_hours_end'] ?? '17:00:00';
            $consultationFee = $doctorData['consultation_fee'] ?? 0.00;
            $experienceYears = $doctorData['experience_years'] ?? 0;

            $stmt->bindParam(':user_id', $doctorData['user_id']);
            $stmt->bindParam(':specialization', $doctorData['specialization']);
            $stmt->bindParam(':license_number', $doctorData['license_number']);
            $stmt->bindParam(':consultation_fee', $consultationFee);
            $stmt->bindParam(':experience_years', $experienceYears);
            $stmt->bindParam(':bio', $doctorData['bio']);
            $stmt->bindParam(':available_days', $availableDays);
            $stmt->bindParam(':working_hours_start', $workingHoursStart);
            $stmt->bindParam(':working_hours_end', $workingHoursEnd);

            if ($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                logActivity('doctor_profile_created', "Doctor profile created for user ID: " . $doctorData['user_id']);
                
                return [
                    'success' => true,
                    'doctor_id' => $this->id,
                    'message' => 'Doctor profile created successfully'
                ];
            }

            return [
                'success' => false,
                'errors' => ['Failed to create doctor profile. Please try again.']
            ];

        } catch (PDOException $e) {
            error_log("Doctor profile creation error: " . $e->getMessage());
            return [
                'success' => false,
                'errors' => ['Failed to create doctor profile. Please try again.']
            ];
        }
    }

    /**
     * Get doctor by user ID
     * @param int $userId
     * @return array|false
     */
    public function getByUserId($userId) {
        try {
            $query = "SELECT d.*, u.first_name, u.last_name, u.email, u.phone 
                     FROM " . $this->table_name . " d
                     INNER JOIN " . $this->users_table . " u ON d.user_id = u.id
                     WHERE d.user_id = :user_id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();

            if ($stmt->rowCount() == 1) {
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }

            return false;

        } catch (PDOException $e) {
            error_log("Get doctor by user ID error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get doctor by ID
     * @param int $id
     * @return array|false
     */
    public function getById($id) {
        try {
            $query = "SELECT d.*, u.first_name, u.last_name, u.email, u.phone 
                     FROM " . $this->table_name . " d
                     INNER JOIN " . $this->users_table . " u ON d.user_id = u.id
                     WHERE d.id = :id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            if ($stmt->rowCount() == 1) {
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }

            return false;

        } catch (PDOException $e) {
            error_log("Get doctor by ID error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all doctors with filters
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getAllDoctors($filters = [], $limit = 50, $offset = 0) {
        try {
            $query = "SELECT d.*, u.first_name, u.last_name, u.email, u.phone 
                     FROM " . $this->table_name . " d
                     INNER JOIN " . $this->users_table . " u ON d.user_id = u.id
                     WHERE u.is_active = 1";

            $params = [];

            // Apply filters
            if (!empty($filters['specialization'])) {
                $query .= " AND d.specialization LIKE :specialization";
                $params[':specialization'] = '%' . $filters['specialization'] . '%';
            }

            if (!empty($filters['is_available'])) {
                $query .= " AND d.is_available = :is_available";
                $params[':is_available'] = $filters['is_available'];
            }

            if (!empty($filters['search'])) {
                $query .= " AND (u.first_name LIKE :search OR u.last_name LIKE :search OR d.specialization LIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }

            // Add ordering
            $query .= " ORDER BY u.first_name, u.last_name";

            // Add pagination
            $query .= " LIMIT :limit OFFSET :offset";

            $stmt = $this->conn->prepare($query);

            // Bind filter parameters
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            // Bind pagination parameters
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Get all doctors error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update doctor profile
     * @param int $id
     * @param array $doctorData
     * @return array
     */
    public function update($id, $doctorData) {
        try {
            // Validate input data
            $validation = $this->validateDoctorData($doctorData, true);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'errors' => $validation['errors']
                ];
            }

            // Check if license number exists for other doctors
            if (isset($doctorData['license_number']) && $this->licenseExistsForOtherDoctor($doctorData['license_number'], $id)) {
                return [
                    'success' => false,
                    'errors' => ['License number is already in use by another doctor']
                ];
            }

            // Build update query dynamically
            $updateFields = [];
            $params = [':id' => $id];

            $allowedFields = ['specialization', 'license_number', 'consultation_fee', 'experience_years', 'bio', 'available_days', 'working_hours_start', 'working_hours_end', 'is_available'];

            foreach ($allowedFields as $field) {
                if (isset($doctorData[$field])) {
                    $updateFields[] = "$field = :$field";
                    $params[":$field"] = $doctorData[$field];
                }
            }

            if (empty($updateFields)) {
                return [
                    'success' => false,
                    'errors' => ['No valid fields to update']
                ];
            }

            $query = "UPDATE " . $this->table_name . " 
                     SET " . implode(', ', $updateFields) . ", updated_at = NOW() 
                     WHERE id = :id";

            $stmt = $this->conn->prepare($query);

            if ($stmt->execute($params)) {
                logActivity('doctor_profile_updated', "Doctor profile updated for ID: " . $id);
                
                return [
                    'success' => true,
                    'message' => 'Doctor profile updated successfully'
                ];
            }

            return [
                'success' => false,
                'errors' => ['Failed to update doctor profile. Please try again.']
            ];

        } catch (PDOException $e) {
            error_log("Doctor profile update error: " . $e->getMessage());
            return [
                'success' => false,
                'errors' => ['Failed to update doctor profile. Please try again.']
            ];
        }
    }

    /**
     * Update doctor availability
     * @param int $id
     * @param bool $isAvailable
     * @return array
     */
    public function updateAvailability($id, $isAvailable) {
        try {
            $query = "UPDATE " . $this->table_name . " 
                     SET is_available = :is_available, updated_at = NOW() 
                     WHERE id = :id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':is_available', $isAvailable, PDO::PARAM_BOOL);
            $stmt->bindParam(':id', $id);

            if ($stmt->execute()) {
                $status = $isAvailable ? 'available' : 'unavailable';
                logActivity('doctor_availability_updated', "Doctor availability set to $status for ID: " . $id);
                
                return [
                    'success' => true,
                    'message' => 'Availability updated successfully'
                ];
            }

            return [
                'success' => false,
                'errors' => ['Failed to update availability. Please try again.']
            ];

        } catch (PDOException $e) {
            error_log("Doctor availability update error: " . $e->getMessage());
            return [
                'success' => false,
                'errors' => ['Failed to update availability. Please try again.']
            ];
        }
    }

    /**
     * Get doctor's schedule for a date range
     * @param int $doctorId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getSchedule($doctorId, $startDate, $endDate) {
        try {
            $query = "SELECT appointment_date, appointment_time, duration_minutes, status, reason,
                            p.first_name as patient_first_name, p.last_name as patient_last_name
                     FROM appointments a
                     INNER JOIN users p ON a.patient_id = p.id
                     WHERE a.doctor_id = :doctor_id 
                     AND a.appointment_date BETWEEN :start_date AND :end_date
                     AND a.status NOT IN ('cancelled', 'no_show')
                     ORDER BY a.appointment_date, a.appointment_time";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':doctor_id', $doctorId);
            $stmt->bindParam(':start_date', $startDate);
            $stmt->bindParam(':end_date', $endDate);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Get doctor schedule error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get doctor statistics
     * @param int $doctorId
     * @param array $filters
     * @return array
     */
    public function getStatistics($doctorId, $filters = []) {
        try {
            $baseQuery = "FROM appointments a WHERE a.doctor_id = :doctor_id";
            $params = [':doctor_id' => $doctorId];

            // Apply date filters
            if (!empty($filters['date_from'])) {
                $baseQuery .= " AND a.appointment_date >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }

            if (!empty($filters['date_to'])) {
                $baseQuery .= " AND a.appointment_date <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }

            // Get total appointments
            $totalQuery = "SELECT COUNT(*) as total " . $baseQuery;
            $totalStmt = $this->conn->prepare($totalQuery);
            foreach ($params as $key => $value) {
                $totalStmt->bindValue($key, $value);
            }
            $totalStmt->execute();
            $total = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Get appointments by status
            $statusQuery = "SELECT status, COUNT(*) as count " . $baseQuery . " GROUP BY status";
            $statusStmt = $this->conn->prepare($statusQuery);
            foreach ($params as $key => $value) {
                $statusStmt->bindValue($key, $value);
            }
            $statusStmt->execute();
            $statusCounts = $statusStmt->fetchAll(PDO::FETCH_ASSOC);

            // Get upcoming appointments
            $upcomingQuery = "SELECT COUNT(*) as count " . $baseQuery . " AND a.appointment_date >= CURDATE() AND a.status = 'scheduled'";
            $upcomingStmt = $this->conn->prepare($upcomingQuery);
            foreach ($params as $key => $value) {
                $upcomingStmt->bindValue($key, $value);
            }
            $upcomingStmt->execute();
            $upcoming = $upcomingStmt->fetch(PDO::FETCH_ASSOC)['count'];

            // Get today's appointments
            $todayQuery = "SELECT COUNT(*) as count " . $baseQuery . " AND a.appointment_date = CURDATE() AND a.status IN ('scheduled', 'confirmed')";
            $todayStmt = $this->conn->prepare($todayQuery);
            foreach ($params as $key => $value) {
                $todayStmt->bindValue($key, $value);
            }
            $todayStmt->execute();
            $today = $todayStmt->fetch(PDO::FETCH_ASSOC)['count'];

            return [
                'total' => $total,
                'upcoming' => $upcoming,
                'today' => $today,
                'by_status' => $statusCounts
            ];

        } catch (PDOException $e) {
            error_log("Get doctor statistics error: " . $e->getMessage());
            return [
                'total' => 0,
                'upcoming' => 0,
                'today' => 0,
                'by_status' => []
            ];
        }
    }

    /**
     * Get available specializations
     * @return array
     */
    public function getSpecializations() {
        try {
            $query = "SELECT DISTINCT specialization FROM " . $this->table_name . " ORDER BY specialization";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $specializations = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $specializations[] = $row['specialization'];
            }
            
            return $specializations;

        } catch (PDOException $e) {
            error_log("Get specializations error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if license number exists
     * @param string $licenseNumber
     * @return bool
     */
    private function licenseExists($licenseNumber) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE license_number = :license_number";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':license_number', $licenseNumber);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /**
     * Check if license number exists for other doctor
     * @param string $licenseNumber
     * @param int $doctorId
     * @return bool
     */
    private function licenseExistsForOtherDoctor($licenseNumber, $doctorId) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE license_number = :license_number AND id != :doctor_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':license_number', $licenseNumber);
        $stmt->bindParam(':doctor_id', $doctorId);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /**
     * Validate doctor data
     * @param array $data
     * @param bool $isUpdate
     * @return array
     */
    private function validateDoctorData($data, $isUpdate = false) {
        $errors = [];

        if (!$isUpdate || isset($data['user_id'])) {
            if (empty($data['user_id'])) {
                $errors[] = "User ID is required";
            }
        }

        if (!$isUpdate || isset($data['specialization'])) {
            if (empty($data['specialization'])) {
                $errors[] = "Specialization is required";
            }
        }

        if (!$isUpdate || isset($data['license_number'])) {
            if (empty($data['license_number'])) {
                $errors[] = "License number is required";
            }
        }

        if (isset($data['consultation_fee']) && (!is_numeric($data['consultation_fee']) || $data['consultation_fee'] < 0)) {
            $errors[] = "Consultation fee must be a valid positive number";
        }

        if (isset($data['experience_years']) && (!is_numeric($data['experience_years']) || $data['experience_years'] < 0 || $data['experience_years'] > 50)) {
            $errors[] = "Experience years must be between 0 and 50";
        }

        if (isset($data['working_hours_start']) && !validateTime($data['working_hours_start'])) {
            $errors[] = "Invalid working hours start time format";
        }

        if (isset($data['working_hours_end']) && !validateTime($data['working_hours_end'])) {
            $errors[] = "Invalid working hours end time format";
        }

        if (isset($data['available_days'])) {
            $validDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            $availableDays = is_string($data['available_days']) ? json_decode($data['available_days'], true) : $data['available_days'];
            
            if (!is_array($availableDays)) {
                $errors[] = "Available days must be an array";
            } else {
                foreach ($availableDays as $day) {
                    if (!in_array($day, $validDays)) {
                        $errors[] = "Invalid day: $day";
                        break;
                    }
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
?>