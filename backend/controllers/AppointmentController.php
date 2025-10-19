<?php
/**
 * Appointment Controller
 * Handles appointment-related operations
 */

class AppointmentController {
    private $db;
    private $appointment;
    
    public function __construct($database) {
        $this->db = $database;
        $this->appointment = new Appointment($this->db);
    }
    
    /**
     * Book a new appointment
     */
    public function bookAppointment($appointmentData) {
        try {
            // Validate appointment data
            $validation = $this->validateAppointmentData($appointmentData);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => $validation['message']
                ];
            }
            
            // Check doctor availability
            if (!$this->appointment->isDoctorAvailable(
                $appointmentData['doctor_id'],
                $appointmentData['appointment_date'],
                $appointmentData['appointment_time']
            )) {
                return [
                    'success' => false,
                    'message' => 'Doctor is not available at the selected time'
                ];
            }
            
            // Create appointment
            $appointmentId = $this->appointment->create($appointmentData);
            
            if ($appointmentId) {
                return [
                    'success' => true,
                    'message' => 'Appointment booked successfully',
                    'appointment_id' => $appointmentId
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to book appointment'
                ];
            }
        } catch (Exception $e) {
            error_log("Appointment booking error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while booking the appointment'
            ];
        }
    }
    
    /**
     * Cancel an appointment
     */
    public function cancelAppointment($appointmentId, $userId, $userRole) {
        try {
            // Verify appointment ownership or admin access
            if (!$this->appointment->canUserModifyAppointment($appointmentId, $userId, $userRole)) {
                return [
                    'success' => false,
                    'message' => 'You do not have permission to cancel this appointment'
                ];
            }
            
            // Cancel appointment
            if ($this->appointment->updateStatus($appointmentId, 'cancelled')) {
                return [
                    'success' => true,
                    'message' => 'Appointment cancelled successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to cancel appointment'
                ];
            }
        } catch (Exception $e) {
            error_log("Appointment cancellation error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while cancelling the appointment'
            ];
        }
    }
    
    /**
     * Get appointments with filters
     */
    public function getAppointments($filters = [], $limit = 50) {
        try {
            return $this->appointment->getAppointments($filters, $limit);
        } catch (Exception $e) {
            error_log("Get appointments error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get appointment statistics
     */
    public function getStatistics($filters = []) {
        try {
            return $this->appointment->getStatistics($filters);
        } catch (Exception $e) {
            error_log("Get statistics error: " . $e->getMessage());
            return [
                'total' => 0,
                'upcoming' => 0,
                'by_status' => []
            ];
        }
    }
    
    /**
     * Validate appointment data
     */
    private function validateAppointmentData($data) {
        $required = ['patient_id', 'doctor_id', 'appointment_date', 'appointment_time'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return [
                    'valid' => false,
                    'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required'
                ];
            }
        }
        
        // Date validation
        $appointmentDate = strtotime($data['appointment_date']);
        if ($appointmentDate < strtotime('today')) {
            return [
                'valid' => false,
                'message' => 'Appointment date cannot be in the past'
            ];
        }
        
        // Time validation
        if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $data['appointment_time'])) {
            return [
                'valid' => false,
                'message' => 'Invalid time format'
            ];
        }
        
        return ['valid' => true];
    }
}