<?php
/**
 * Appointment Class
 * Handles CRUD operations for medical appointments
 */

require_once '../config/database.php';
require_once '../includes/helpers.php';
require_once 'RoleManager.php';

class Appointment {
    private $conn;
    private $table_name = "appointments";
    private $roleManager;

    public $id;
    public $patient_id;
    public $doctor_id;
    public $appointment_date;
    public $appointment_time;
    public $duration_minutes;
    public $status;
    public $reason;
    public $notes;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
        $this->roleManager = new RoleManager($db);
    }

    /**
     * Create a new appointment
     * @param array $appointmentData
     * @return array
     */
    public function create($appointmentData) {
        try {
            // Validate input data
            $validation = $this->validateAppointmentData($appointmentData);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'errors' => $validation['errors']
                ];
            }

            // Check if doctor is available at the requested time
            if (!$this->isDoctorAvailable($appointmentData['doctor_id'], $appointmentData['appointment_date'], $appointmentData['appointment_time'])) {
                return [
                    'success' => false,
                    'errors' => ['Doctor is not available at the requested time']
                ];
            }

            // Check for conflicting appointments
            if ($this->hasConflictingAppointment($appointmentData['doctor_id'], $appointmentData['appointment_date'], $appointmentData['appointment_time'])) {
                return [
                    'success' => false,
                    'errors' => ['This time slot is already booked']
                ];
            }

            $query = "INSERT INTO " . $this->table_name . " 
                     (patient_id, doctor_id, appointment_date, appointment_time, duration_minutes, reason, status) 
                     VALUES (:patient_id, :doctor_id, :appointment_date, :appointment_time, :duration_minutes, :reason, :status)";

            $stmt = $this->conn->prepare($query);

            // Set default values
            $status = $appointmentData['status'] ?? 'scheduled';
            $duration = $appointmentData['duration_minutes'] ?? 30;

            $stmt->bindParam(':patient_id', $appointmentData['patient_id']);
            $stmt->bindParam(':doctor_id', $appointmentData['doctor_id']);
            $stmt->bindParam(':appointment_date', $appointmentData['appointment_date']);
            $stmt->bindParam(':appointment_time', $appointmentData['appointment_time']);
            $stmt->bindParam(':duration_minutes', $duration);
            $stmt->bindParam(':reason', $appointmentData['reason']);
            $stmt->bindParam(':status', $status);

            if ($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                
                $this->roleManager->logRoleAction('appointment_created', 'appointment', $this->id);
                
                return [
                    'success' => true,
                    'appointment_id' => $this->id,
                    'message' => 'Appointment booked successfully'
                ];
            }

            return [
                'success' => false,
                'errors' => ['Failed to book appointment. Please try again.']
            ];

        } catch (PDOException $e) {
            error_log("Appointment creation error: " . $e->getMessage());
            return [
                'success' => false,
                'errors' => ['Failed to book appointment. Please try again.']
            ];
        }
    }

    /**
     * Get appointment by ID
     * @param int $id
     * @return array|false
     */
    public function getById($id) {
        try {
            // Check if user can access this appointment
            if (!$this->roleManager->canAccessAppointment($id)) {
                return false;
            }

            $query = "SELECT a.*, 
                            p.first_name as patient_first_name, p.last_name as patient_last_name, 
                            p.email as patient_email, p.phone as patient_phone,
                            u.first_name as doctor_first_name, u.last_name as doctor_last_name,
                            d.specialization, d.consultation_fee
                     FROM " . $this->table_name . " a
                     INNER JOIN users p ON a.patient_id = p.id
                     INNER JOIN doctors doc ON a.doctor_id = doc.id
                     INNER JOIN users u ON doc.user_id = u.id
                     INNER JOIN doctors d ON a.doctor_id = d.id
                     WHERE a.id = :id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            if ($stmt->rowCount() == 1) {
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }

            return false;

        } catch (PDOException $e) {
            error_log("Get appointment error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get appointments with filters
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getAppointments($filters = [], $limit = 50, $offset = 0) {
        try {
            $baseQuery = "SELECT a.*, 
                                p.first_name as patient_first_name, p.last_name as patient_last_name, 
                                p.email as patient_email, p.phone as patient_phone,
                                u.first_name as doctor_first_name, u.last_name as doctor_last_name,
                                d.specialization, d.consultation_fee
                         FROM " . $this->table_name . " a
                         INNER JOIN users p ON a.patient_id = p.id
                         INNER JOIN doctors doc ON a.doctor_id = doc.id
                         INNER JOIN users u ON doc.user_id = u.id
                         INNER JOIN doctors d ON a.doctor_id = d.id
                         WHERE 1=1";

            // Apply role-based filtering
            $query = $this->roleManager->getFilteredAppointmentQuery($baseQuery);

            $params = [];

            // Apply additional filters
            if (!empty($filters['status'])) {
                $query .= " AND a.status = :status";
                $params[':status'] = $filters['status'];
            }

            if (!empty($filters['date_from'])) {
                $query .= " AND a.appointment_date >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }

            if (!empty($filters['date_to'])) {
                $query .= " AND a.appointment_date <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }

            if (!empty($filters['doctor_id'])) {
                $query .= " AND a.doctor_id = :doctor_id";
                $params[':doctor_id'] = $filters['doctor_id'];
            }

            if (!empty($filters['patient_id'])) {
                $query .= " AND a.patient_id = :patient_id";
                $params[':patient_id'] = $filters['patient_id'];
            }

            // Add ordering
            $query .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";

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
            error_log("Get appointments error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update appointment
     * @param int $id
     * @param array $appointmentData
     * @return array
     */
    public function update($id, $appointmentData) {
        try {
            // Check if user can access this appointment
            if (!$this->roleManager->canAccessAppointment($id)) {
                return [
                    'success' => false,
                    'errors' => ['You do not have permission to update this appointment']
                ];
            }

            // Validate input data
            $validation = $this->validateAppointmentData($appointmentData, true);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'errors' => $validation['errors']
                ];
            }

            // If date/time is being changed, check availability
            if (isset($appointmentData['appointment_date']) || isset($appointmentData['appointment_time'])) {
                $currentAppointment = $this->getById($id);
                if (!$currentAppointment) {
                    return [
                        'success' => false,
                        'errors' => ['Appointment not found']
                    ];
                }

                $newDate = $appointmentData['appointment_date'] ?? $currentAppointment['appointment_date'];
                $newTime = $appointmentData['appointment_time'] ?? $currentAppointment['appointment_time'];
                $doctorId = $appointmentData['doctor_id'] ?? $currentAppointment['doctor_id'];

                // Check if the new time conflicts with other appointments (excluding current one)
                if ($this->hasConflictingAppointment($doctorId, $newDate, $newTime, $id)) {
                    return [
                        'success' => false,
                        'errors' => ['This time slot is already booked']
                    ];
                }
            }

            // Build update query dynamically
            $updateFields = [];
            $params = [':id' => $id];

            $allowedFields = ['doctor_id', 'appointment_date', 'appointment_time', 'duration_minutes', 'status', 'reason', 'notes'];

            foreach ($allowedFields as $field) {
                if (isset($appointmentData[$field])) {
                    $updateFields[] = "$field = :$field";
                    $params[":$field"] = $appointmentData[$field];
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
                $this->roleManager->logRoleAction('appointment_updated', 'appointment', $id);
                
                return [
                    'success' => true,
                    'message' => 'Appointment updated successfully'
                ];
            }

            return [
                'success' => false,
                'errors' => ['Failed to update appointment. Please try again.']
            ];

        } catch (PDOException $e) {
            error_log("Appointment update error: " . $e->getMessage());
            return [
                'success' => false,
                'errors' => ['Failed to update appointment. Please try again.']
            ];
        }
    }

    /**
     * Cancel appointment
     * @param int $id
     * @param string $reason
     * @return array
     */
    public function cancel($id, $reason = '') {
        try {
            // Check if user can access this appointment
            if (!$this->roleManager->canAccessAppointment($id)) {
                return [
                    'success' => false,
                    'errors' => ['You do not have permission to cancel this appointment']
                ];
            }

            $query = "UPDATE " . $this->table_name . " 
                     SET status = 'cancelled', notes = CONCAT(COALESCE(notes, ''), '\nCancelled: ', :reason), updated_at = NOW() 
                     WHERE id = :id AND status NOT IN ('completed', 'cancelled')";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':reason', $reason);

            if ($stmt->execute() && $stmt->rowCount() > 0) {
                $this->roleManager->logRoleAction('appointment_cancelled', 'appointment', $id);
                
                return [
                    'success' => true,
                    'message' => 'Appointment cancelled successfully'
                ];
            }

            return [
                'success' => false,
                'errors' => ['Failed to cancel appointment. It may already be cancelled or completed.']
            ];

        } catch (PDOException $e) {
            error_log("Appointment cancellation error: " . $e->getMessage());
            return [
                'success' => false,
                'errors' => ['Failed to cancel appointment. Please try again.']
            ];
        }
    }

    /**
     * Delete appointment (admin only)
     * @param int $id
     * @return array
     */
    public function delete($id) {
        try {
            // Only admins can delete appointments
            if (!$this->roleManager->hasRole('admin')) {
                return [
                    'success' => false,
                    'errors' => ['You do not have permission to delete appointments']
                ];
            }

            $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);

            if ($stmt->execute() && $stmt->rowCount() > 0) {
                $this->roleManager->logRoleAction('appointment_deleted', 'appointment', $id);
                
                return [
                    'success' => true,
                    'message' => 'Appointment deleted successfully'
                ];
            }

            return [
                'success' => false,
                'errors' => ['Failed to delete appointment. It may not exist.']
            ];

        } catch (PDOException $e) {
            error_log("Appointment deletion error: " . $e->getMessage());
            return [
                'success' => false,
                'errors' => ['Failed to delete appointment. Please try again.']
            ];
        }
    }

    /**
     * Get available time slots for a doctor on a specific date
     * @param int $doctorId
     * @param string $date
     * @return array
     */
    public function getAvailableTimeSlots($doctorId, $date) {
        try {
            // Get doctor's working hours and available days
            $doctorQuery = "SELECT working_hours_start, working_hours_end, available_days 
                           FROM doctors WHERE id = :doctor_id AND is_available = 1";
            $doctorStmt = $this->conn->prepare($doctorQuery);
            $doctorStmt->bindParam(':doctor_id', $doctorId);
            $doctorStmt->execute();

            if ($doctorStmt->rowCount() == 0) {
                return [];
            }

            $doctor = $doctorStmt->fetch(PDO::FETCH_ASSOC);
            $availableDays = json_decode($doctor['available_days'], true);
            $dayOfWeek = date('l', strtotime($date));

            // Check if doctor is available on this day
            if (!in_array($dayOfWeek, $availableDays)) {
                return [];
            }

            // Generate time slots
            $startTime = strtotime($doctor['working_hours_start']);
            $endTime = strtotime($doctor['working_hours_end']);
            $slotDuration = 30 * 60; // 30 minutes in seconds

            $timeSlots = [];
            for ($time = $startTime; $time < $endTime; $time += $slotDuration) {
                $timeSlots[] = date('H:i', $time);
            }

            // Get booked appointments for this date
            $bookedQuery = "SELECT appointment_time FROM " . $this->table_name . " 
                           WHERE doctor_id = :doctor_id AND appointment_date = :date 
                           AND status NOT IN ('cancelled', 'no_show')";
            $bookedStmt = $this->conn->prepare($bookedQuery);
            $bookedStmt->bindParam(':doctor_id', $doctorId);
            $bookedStmt->bindParam(':date', $date);
            $bookedStmt->execute();

            $bookedTimes = [];
            while ($row = $bookedStmt->fetch(PDO::FETCH_ASSOC)) {
                $bookedTimes[] = date('H:i', strtotime($row['appointment_time']));
            }

            // Remove booked time slots
            $availableSlots = array_diff($timeSlots, $bookedTimes);

            return array_values($availableSlots);

        } catch (PDOException $e) {
            error_log("Get available time slots error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get appointment statistics
     * @param array $filters
     * @return array
     */
    public function getStatistics($filters = []) {
        try {
            $baseQuery = "FROM " . $this->table_name . " a
                         INNER JOIN doctors d ON a.doctor_id = d.id
                         WHERE 1=1";

            // Apply role-based filtering
            $query = $this->roleManager->getFilteredAppointmentQuery($baseQuery);

            $params = [];

            // Apply date filters
            if (!empty($filters['date_from'])) {
                $query .= " AND a.appointment_date >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }

            if (!empty($filters['date_to'])) {
                $query .= " AND a.appointment_date <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }

            // Get total appointments
            $totalQuery = "SELECT COUNT(*) as total " . $query;
            $totalStmt = $this->conn->prepare($totalQuery);
            foreach ($params as $key => $value) {
                $totalStmt->bindValue($key, $value);
            }
            $totalStmt->execute();
            $total = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Get appointments by status
            $statusQuery = "SELECT a.status, COUNT(*) as count " . $query . " GROUP BY a.status";
            $statusStmt = $this->conn->prepare($statusQuery);
            foreach ($params as $key => $value) {
                $statusStmt->bindValue($key, $value);
            }
            $statusStmt->execute();
            $statusCounts = $statusStmt->fetchAll(PDO::FETCH_ASSOC);

            // Get upcoming appointments
            $upcomingQuery = "SELECT COUNT(*) as count " . $query . " AND a.appointment_date >= CURDATE() AND a.status = 'scheduled'";
            $upcomingStmt = $this->conn->prepare($upcomingQuery);
            foreach ($params as $key => $value) {
                $upcomingStmt->bindValue($key, $value);
            }
            $upcomingStmt->execute();
            $upcoming = $upcomingStmt->fetch(PDO::FETCH_ASSOC)['count'];

            return [
                'total' => $total,
                'upcoming' => $upcoming,
                'by_status' => $statusCounts
            ];

        } catch (PDOException $e) {
            error_log("Get statistics error: " . $e->getMessage());
            return [
                'total' => 0,
                'upcoming' => 0,
                'by_status' => []
            ];
        }
    }

    /**
     * Check if doctor is available at specified time
     * @param int $doctorId
     * @param string $date
     * @param string $time
     * @return bool
     */
    private function isDoctorAvailable($doctorId, $date, $time) {
        try {
            $query = "SELECT working_hours_start, working_hours_end, available_days 
                     FROM doctors 
                     WHERE id = :doctor_id AND is_available = 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':doctor_id', $doctorId);
            $stmt->execute();

            if ($stmt->rowCount() == 0) {
                return false;
            }

            $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
            $availableDays = json_decode($doctor['available_days'], true);
            $dayOfWeek = date('l', strtotime($date));

            // Check if doctor works on this day
            if (!in_array($dayOfWeek, $availableDays)) {
                return false;
            }

            // Check if time is within working hours
            $appointmentTime = strtotime($time);
            $startTime = strtotime($doctor['working_hours_start']);
            $endTime = strtotime($doctor['working_hours_end']);

            return $appointmentTime >= $startTime && $appointmentTime < $endTime;

        } catch (PDOException $e) {
            error_log("Check doctor availability error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check for conflicting appointments
     * @param int $doctorId
     * @param string $date
     * @param string $time
     * @param int $excludeId
     * @return bool
     */
    private function hasConflictingAppointment($doctorId, $date, $time, $excludeId = null) {
        try {
            $query = "SELECT id FROM " . $this->table_name . " 
                     WHERE doctor_id = :doctor_id 
                     AND appointment_date = :date 
                     AND appointment_time = :time 
                     AND status NOT IN ('cancelled', 'no_show')";

            if ($excludeId) {
                $query .= " AND id != :exclude_id";
            }

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':doctor_id', $doctorId);
            $stmt->bindParam(':date', $date);
            $stmt->bindParam(':time', $time);
            
            if ($excludeId) {
                $stmt->bindParam(':exclude_id', $excludeId);
            }

            $stmt->execute();
            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {
            error_log("Check conflicting appointment error: " . $e->getMessage());
            return true; // Assume conflict on error for safety
        }
    }

    /**
     * Validate appointment data
     * @param array $data
     * @param bool $isUpdate
     * @return array
     */
    private function validateAppointmentData($data, $isUpdate = false) {
        $errors = [];

        if (!$isUpdate || isset($data['patient_id'])) {
            if (empty($data['patient_id'])) {
                $errors[] = "Patient is required";
            }
        }

        if (!$isUpdate || isset($data['doctor_id'])) {
            if (empty($data['doctor_id'])) {
                $errors[] = "Doctor is required";
            }
        }

        if (!$isUpdate || isset($data['appointment_date'])) {
            if (empty($data['appointment_date'])) {
                $errors[] = "Appointment date is required";
            } elseif (!validateDate($data['appointment_date'])) {
                $errors[] = "Invalid date format";
            } elseif (!isFutureDate($data['appointment_date'])) {
                $errors[] = "Appointment date must be in the future";
            }
        }

        if (!$isUpdate || isset($data['appointment_time'])) {
            if (empty($data['appointment_time'])) {
                $errors[] = "Appointment time is required";
            } elseif (!validateTime($data['appointment_time'])) {
                $errors[] = "Invalid time format";
            }
        }

        if (isset($data['status']) && !in_array($data['status'], ['scheduled', 'confirmed', 'completed', 'cancelled', 'no_show'])) {
            $errors[] = "Invalid appointment status";
        }

        if (isset($data['duration_minutes']) && (!is_numeric($data['duration_minutes']) || $data['duration_minutes'] < 15 || $data['duration_minutes'] > 180)) {
            $errors[] = "Duration must be between 15 and 180 minutes";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
?>