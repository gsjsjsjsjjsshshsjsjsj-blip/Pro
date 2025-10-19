<?php
/**
 * Book Appointment Page
 * Patient appointment booking with doctor selection and time slot availability
 */

require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../classes/RoleManager.php';
require_once '../classes/Appointment.php';
require_once '../classes/Doctor.php';

// Require patient role
$database = new Database();
$db = $database->getConnection();
$roleManager = new RoleManager($db);
$roleManager->requireRoleOrHigher('patient');

$appointment = new Appointment($db);
$doctor = new Doctor($db);

$errors = [];
$success = false;
$selectedDoctorId = $_GET['doctor_id'] ?? '';
$availableTimeSlots = [];

// Get all available doctors
$doctors = $doctor->getAllDoctors(['is_available' => 1]);

// Form processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $appointmentData = [
            'patient_id' => $_SESSION['user_id'],
            'doctor_id' => sanitizeInput($_POST['doctor_id'] ?? ''),
            'appointment_date' => sanitizeInput($_POST['appointment_date'] ?? ''),
            'appointment_time' => sanitizeInput($_POST['appointment_time'] ?? ''),
            'reason' => sanitizeInput($_POST['reason'] ?? ''),
            'duration_minutes' => 30
        ];
        
        // Create appointment
        $result = $appointment->create($appointmentData);
        
        if ($result['success']) {
            $success = true;
            setFlashMessage('success', 'Appointment booked successfully! You will receive a confirmation shortly.');
            
            // Redirect to appointments page after a short delay
            echo "<script>
                setTimeout(function() {
                    window.location.href = 'appointments.php';
                }, 2000);
            </script>";
        } else {
            $errors = $result['errors'];
        }
    }
}

// AJAX endpoint for getting available time slots
if (isset($_GET['action']) && $_GET['action'] === 'get_time_slots') {
    header('Content-Type: application/json');
    
    $doctorId = $_GET['doctor_id'] ?? '';
    $date = $_GET['date'] ?? '';
    
    if ($doctorId && $date) {
        $timeSlots = $appointment->getAvailableTimeSlots($doctorId, $date);
        echo json_encode(['success' => true, 'slots' => $timeSlots]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    }
    exit;
}

$pageTitle = 'Book Appointment - Medical Appointment System';
?>

<?php require_once '../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h2 mb-1">Book New Appointment</h1>
        <p class="text-muted mb-0">Schedule your appointment with our healthcare professionals</p>
    </div>
    <div>
        <a href="dashboard.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>
            Back to Dashboard
        </a>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle me-2"></i>
        <strong>Success!</strong> Your appointment has been booked successfully.
        <div class="mt-2">
            <small>You will be redirected to your appointments page shortly...</small>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Please correct the following errors:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-calendar-plus me-2"></i>
                    Appointment Details
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" id="bookingForm" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <!-- Doctor Selection -->
                    <div class="mb-4">
                        <label for="doctor_id" class="form-label">Select Doctor *</label>
                        <select class="form-select" id="doctor_id" name="doctor_id" required>
                            <option value="">Choose a doctor...</option>
                            <?php foreach ($doctors as $doc): ?>
                                <option value="<?php echo $doc['id']; ?>" 
                                        <?php echo $selectedDoctorId == $doc['id'] ? 'selected' : ''; ?>
                                        data-specialization="<?php echo htmlspecialchars($doc['specialization']); ?>"
                                        data-fee="<?php echo $doc['consultation_fee']; ?>">
                                    Dr. <?php echo htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']); ?> 
                                    - <?php echo htmlspecialchars($doc['specialization']); ?>
                                    (Fee: $<?php echo number_format($doc['consultation_fee'], 2); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">
                            Please select a doctor.
                        </div>
                    </div>
                    
                    <!-- Doctor Info Display -->
                    <div id="doctorInfo" class="alert alert-info d-none mb-4">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Specialization:</strong> <span id="doctorSpecialization"></span><br>
                                <strong>Experience:</strong> <span id="doctorExperience"></span> years
                            </div>
                            <div class="col-md-6">
                                <strong>Consultation Fee:</strong> $<span id="doctorFee"></span><br>
                                <strong>Working Hours:</strong> <span id="doctorHours"></span>
                            </div>
                        </div>
                        <div class="mt-2">
                            <strong>Bio:</strong> <span id="doctorBio"></span>
                        </div>
                    </div>
                    
                    <!-- Date Selection -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="appointment_date" class="form-label">Appointment Date *</label>
                            <input type="date" 
                                   class="form-control" 
                                   id="appointment_date" 
                                   name="appointment_date" 
                                   min="<?php echo date('Y-m-d'); ?>"
                                   max="<?php echo date('Y-m-d', strtotime('+3 months')); ?>"
                                   required>
                            <div class="invalid-feedback">
                                Please select a valid appointment date.
                            </div>
                        </div>
                        
                        <!-- Time Selection -->
                        <div class="col-md-6 mb-3">
                            <label for="appointment_time" class="form-label">Appointment Time *</label>
                            <select class="form-select" id="appointment_time" name="appointment_time" required disabled>
                                <option value="">Select date first...</option>
                            </select>
                            <div class="invalid-feedback">
                                Please select an available time slot.
                            </div>
                        </div>
                    </div>
                    
                    <!-- Loading indicator for time slots -->
                    <div id="timeSlotsLoading" class="text-center d-none mb-3">
                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <span class="ms-2">Loading available time slots...</span>
                    </div>
                    
                    <!-- Reason for Visit -->
                    <div class="mb-4">
                        <label for="reason" class="form-label">Reason for Visit</label>
                        <textarea class="form-control" 
                                  id="reason" 
                                  name="reason" 
                                  rows="3" 
                                  placeholder="Please describe the reason for your visit (optional)"></textarea>
                        <div class="form-text">
                            This helps the doctor prepare for your appointment.
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                            <i class="fas fa-calendar-check me-2"></i>
                            Book Appointment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Booking Guidelines -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Booking Guidelines
                </h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <i class="fas fa-check text-success me-2"></i>
                        Appointments can be booked up to 3 months in advance
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check text-success me-2"></i>
                        Each appointment slot is 30 minutes
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check text-success me-2"></i>
                        Please arrive 15 minutes early
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check text-success me-2"></i>
                        Cancellations must be made 24 hours in advance
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check text-success me-2"></i>
                        Bring valid ID and insurance information
                    </li>
                </ul>
                
                <hr>
                
                <h6 class="mb-3">Need Help?</h6>
                <p class="small text-muted mb-2">
                    If you need assistance booking your appointment, please contact us:
                </p>
                <p class="small mb-0">
                    <i class="fas fa-phone me-2"></i>
                    <strong>Phone:</strong> (555) 123-4567<br>
                    <i class="fas fa-envelope me-2"></i>
                    <strong>Email:</strong> support@medical.local
                </p>
            </div>
        </div>
        
        <!-- Available Doctors -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user-md me-2"></i>
                    Available Doctors
                </h5>
            </div>
            <div class="card-body">
                <?php foreach (array_slice($doctors, 0, 5) as $doc): ?>
                    <div class="d-flex align-items-center mb-3 doctor-card" 
                         data-doctor-id="<?php echo $doc['id']; ?>"
                         style="cursor: pointer;">
                        <div class="flex-shrink-0">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                 style="width: 40px; height: 40px;">
                                <i class="fas fa-user-md"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0">
                                Dr. <?php echo htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']); ?>
                            </h6>
                            <small class="text-muted"><?php echo htmlspecialchars($doc['specialization']); ?></small><br>
                            <small class="text-success">$<?php echo number_format($doc['consultation_fee'], 2); ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const doctorSelect = document.getElementById('doctor_id');
    const dateInput = document.getElementById('appointment_date');
    const timeSelect = document.getElementById('appointment_time');
    const doctorInfo = document.getElementById('doctorInfo');
    const timeSlotsLoading = document.getElementById('timeSlotsLoading');
    const form = document.getElementById('bookingForm');
    
    // Doctor selection change
    doctorSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        
        if (this.value) {
            // Show doctor info
            showDoctorInfo(this.value);
            
            // Reset and enable date input
            dateInput.disabled = false;
            timeSelect.innerHTML = '<option value="">Select date first...</option>';
            timeSelect.disabled = true;
        } else {
            doctorInfo.classList.add('d-none');
            dateInput.disabled = true;
            timeSelect.disabled = true;
        }
    });
    
    // Date selection change
    dateInput.addEventListener('change', function() {
        if (this.value && doctorSelect.value) {
            loadTimeSlots(doctorSelect.value, this.value);
        }
    });
    
    // Doctor card click handlers
    document.querySelectorAll('.doctor-card').forEach(card => {
        card.addEventListener('click', function() {
            const doctorId = this.dataset.doctorId;
            doctorSelect.value = doctorId;
            doctorSelect.dispatchEvent(new Event('change'));
        });
    });
    
    // Form validation
    form.addEventListener('submit', function(e) {
        if (!validateForm('bookingForm')) {
            e.preventDefault();
            e.stopPropagation();
        }
        form.classList.add('was-validated');
    });
    
    function showDoctorInfo(doctorId) {
        // In a real implementation, you'd fetch this via AJAX
        // For now, we'll use the data from the select option
        const selectedOption = doctorSelect.options[doctorSelect.selectedIndex];
        
        if (selectedOption) {
            document.getElementById('doctorSpecialization').textContent = selectedOption.dataset.specialization;
            document.getElementById('doctorFee').textContent = parseFloat(selectedOption.dataset.fee).toFixed(2);
            
            // You would fetch additional data via AJAX here
            document.getElementById('doctorExperience').textContent = '10'; // Placeholder
            document.getElementById('doctorHours').textContent = '9:00 AM - 5:00 PM'; // Placeholder
            document.getElementById('doctorBio').textContent = 'Experienced healthcare professional dedicated to providing quality care.'; // Placeholder
            
            doctorInfo.classList.remove('d-none');
        }
    }
    
    function loadTimeSlots(doctorId, date) {
        timeSlotsLoading.classList.remove('d-none');
        timeSelect.disabled = true;
        timeSelect.innerHTML = '<option value="">Loading...</option>';
        
        fetch(`?action=get_time_slots&doctor_id=${doctorId}&date=${date}`)
            .then(response => response.json())
            .then(data => {
                timeSlotsLoading.classList.add('d-none');
                
                if (data.success && data.slots.length > 0) {
                    timeSelect.innerHTML = '<option value="">Select a time...</option>';
                    data.slots.forEach(slot => {
                        const option = document.createElement('option');
                        option.value = slot;
                        option.textContent = formatTime(slot);
                        timeSelect.appendChild(option);
                    });
                    timeSelect.disabled = false;
                } else {
                    timeSelect.innerHTML = '<option value="">No available slots</option>';
                    timeSelect.disabled = true;
                }
            })
            .catch(error => {
                console.error('Error loading time slots:', error);
                timeSlotsLoading.classList.add('d-none');
                timeSelect.innerHTML = '<option value="">Error loading slots</option>';
                timeSelect.disabled = true;
            });
    }
    
    function formatTime(timeString) {
        const time = new Date('1970-01-01T' + timeString);
        return time.toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
    }
    
    // Initialize if doctor is pre-selected
    if (doctorSelect.value) {
        showDoctorInfo(doctorSelect.value);
        dateInput.disabled = false;
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>