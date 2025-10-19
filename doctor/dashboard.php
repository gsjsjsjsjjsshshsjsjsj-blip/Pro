<?php
/**
 * Doctor Dashboard
 * Overview of appointments, schedule, and patient management
 */

require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../classes/RoleManager.php';
require_once '../classes/Appointment.php';
require_once '../classes/Doctor.php';

// Require doctor role
$database = new Database();
$db = $database->getConnection();
$roleManager = new RoleManager($db);
$roleManager->requireRoleOrHigher('doctor');

$appointment = new Appointment($db);
$doctor = new Doctor($db);

// Get doctor profile
$doctorProfile = $doctor->getByUserId($_SESSION['user_id']);
if (!$doctorProfile) {
    setFlashMessage('error', 'Doctor profile not found. Please contact administrator.');
    redirect('../auth/logout.php');
}

$doctorId = $doctorProfile['id'];

// Get today's appointments
$todayAppointments = $appointment->getAppointments([
    'doctor_id' => $doctorId,
    'date_from' => date('Y-m-d'),
    'date_to' => date('Y-m-d')
], 20);

// Get upcoming appointments (next 7 days)
$upcomingAppointments = $appointment->getAppointments([
    'doctor_id' => $doctorId,
    'date_from' => date('Y-m-d'),
    'date_to' => date('Y-m-d', strtotime('+7 days'))
], 10);

// Get statistics
$stats = $doctor->getStatistics($doctorId);

$pageTitle = 'Doctor Dashboard - Medical Appointment System';
?>

<?php require_once '../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h2 mb-1">Welcome, Dr. <?php echo htmlspecialchars($doctorProfile['first_name'] . ' ' . $doctorProfile['last_name']); ?>!</h1>
        <p class="text-muted mb-0">
            <?php echo htmlspecialchars($doctorProfile['specialization']); ?> • 
            <?php echo $doctorProfile['experience_years']; ?> years experience
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="availability.php" class="btn btn-outline-primary">
            <i class="fas fa-clock me-2"></i>
            Manage Availability
        </a>
        <a href="schedule.php" class="btn btn-primary">
            <i class="fas fa-calendar me-2"></i>
            View Schedule
        </a>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-0">Total Appointments</h6>
                        <h2 class="mb-0"><?php echo $stats['total']; ?></h2>
                    </div>
                    <div class="opacity-75">
                        <i class="fas fa-calendar-check fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-0">Today's Appointments</h6>
                        <h2 class="mb-0"><?php echo $stats['today']; ?></h2>
                    </div>
                    <div class="opacity-75">
                        <i class="fas fa-calendar-day fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-0">Upcoming</h6>
                        <h2 class="mb-0"><?php echo $stats['upcoming']; ?></h2>
                    </div>
                    <div class="opacity-75">
                        <i class="fas fa-clock fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-0">Availability</h6>
                        <h2 class="mb-0">
                            <?php echo $doctorProfile['is_available'] ? 'ON' : 'OFF'; ?>
                        </h2>
                    </div>
                    <div class="opacity-75">
                        <i class="fas fa-<?php echo $doctorProfile['is_available'] ? 'toggle-on' : 'toggle-off'; ?> fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Today's Schedule -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-calendar-day me-2"></i>
                    Today's Schedule - <?php echo formatDate(date('Y-m-d')); ?>
                </h5>
                <a href="schedule.php" class="btn btn-sm btn-outline-primary">
                    View Full Schedule
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($todayAppointments)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">No appointments scheduled for today</h6>
                        <p class="text-muted mb-0">Enjoy your free day!</p>
                    </div>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($todayAppointments as $apt): ?>
                            <div class="timeline-item appointment-card mb-3">
                                <div class="card border-start border-primary border-4">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center mb-2">
                                                    <h6 class="mb-0 me-3">
                                                        <?php echo formatTime($apt['appointment_time']); ?>
                                                    </h6>
                                                    <span class="badge bg-<?php 
                                                        echo $apt['status'] === 'completed' ? 'success' : 
                                                            ($apt['status'] === 'confirmed' ? 'info' : 'primary'); 
                                                    ?> status-badge">
                                                        <?php echo ucfirst($apt['status']); ?>
                                                    </span>
                                                </div>
                                                <h6 class="mb-1">
                                                    <?php echo htmlspecialchars($apt['patient_first_name'] . ' ' . $apt['patient_last_name']); ?>
                                                </h6>
                                                <?php if (!empty($apt['reason'])): ?>
                                                    <p class="text-muted mb-1">
                                                        <i class="fas fa-comment me-2"></i>
                                                        <?php echo htmlspecialchars($apt['reason']); ?>
                                                    </p>
                                                <?php endif; ?>
                                                <p class="text-muted mb-0">
                                                    <i class="fas fa-clock me-2"></i>
                                                    Duration: <?php echo $apt['duration_minutes']; ?> minutes
                                                </p>
                                            </div>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                        type="button" 
                                                        data-bs-toggle="dropdown">
                                                    Actions
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a class="dropdown-item" href="appointment-details.php?id=<?php echo $apt['id']; ?>">
                                                            <i class="fas fa-eye me-2"></i>View Details
                                                        </a>
                                                    </li>
                                                    <?php if ($apt['status'] === 'scheduled'): ?>
                                                        <li>
                                                            <a class="dropdown-item" href="confirm-appointment.php?id=<?php echo $apt['id']; ?>">
                                                                <i class="fas fa-check me-2"></i>Confirm
                                                            </a>
                                                        </li>
                                                    <?php endif; ?>
                                                    <?php if (in_array($apt['status'], ['scheduled', 'confirmed'])): ?>
                                                        <li>
                                                            <a class="dropdown-item" href="complete-appointment.php?id=<?php echo $apt['id']; ?>">
                                                                <i class="fas fa-check-circle me-2"></i>Mark Complete
                                                            </a>
                                                        </li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions & Profile Summary -->
    <div class="col-lg-4">
        <!-- Quick Actions -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-bolt me-2"></i>
                    Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="appointments.php" class="btn btn-primary">
                        <i class="fas fa-calendar-check me-2"></i>
                        View All Appointments
                    </a>
                    <a href="schedule.php" class="btn btn-outline-primary">
                        <i class="fas fa-calendar me-2"></i>
                        Weekly Schedule
                    </a>
                    <a href="availability.php" class="btn btn-outline-info">
                        <i class="fas fa-clock me-2"></i>
                        Update Availability
                    </a>
                    <a href="profile.php" class="btn btn-outline-secondary">
                        <i class="fas fa-user me-2"></i>
                        Edit Profile
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Profile Summary -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user-md me-2"></i>
                    Profile Summary
                </h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" 
                         style="width: 80px; height: 80px;">
                        <i class="fas fa-user-md fa-2x"></i>
                    </div>
                </div>
                
                <div class="mb-3">
                    <strong>Specialization:</strong><br>
                    <span class="text-muted"><?php echo htmlspecialchars($doctorProfile['specialization']); ?></span>
                </div>
                
                <div class="mb-3">
                    <strong>Experience:</strong><br>
                    <span class="text-muted"><?php echo $doctorProfile['experience_years']; ?> years</span>
                </div>
                
                <div class="mb-3">
                    <strong>License Number:</strong><br>
                    <span class="text-muted"><?php echo htmlspecialchars($doctorProfile['license_number']); ?></span>
                </div>
                
                <div class="mb-3">
                    <strong>Consultation Fee:</strong><br>
                    <span class="text-muted">$<?php echo number_format($doctorProfile['consultation_fee'], 2); ?></span>
                </div>
                
                <div class="mb-3">
                    <strong>Working Hours:</strong><br>
                    <span class="text-muted">
                        <?php echo formatTime($doctorProfile['working_hours_start']); ?> - 
                        <?php echo formatTime($doctorProfile['working_hours_end']); ?>
                    </span>
                </div>
                
                <div class="mb-0">
                    <strong>Status:</strong><br>
                    <span class="badge bg-<?php echo $doctorProfile['is_available'] ? 'success' : 'danger'; ?>">
                        <?php echo $doctorProfile['is_available'] ? 'Available' : 'Unavailable'; ?>
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Upcoming Appointments -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-clock me-2"></i>
                    Next 7 Days
                </h5>
                <a href="appointments.php" class="btn btn-sm btn-outline-primary">
                    View All
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($upcomingAppointments)): ?>
                    <p class="text-muted text-center mb-0">No upcoming appointments</p>
                <?php else: ?>
                    <?php foreach (array_slice($upcomingAppointments, 0, 5) as $apt): ?>
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                     style="width: 35px; height: 35px;">
                                    <i class="fas fa-user"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0">
                                    <?php echo htmlspecialchars($apt['patient_first_name'] . ' ' . $apt['patient_last_name']); ?>
                                </h6>
                                <small class="text-muted">
                                    <?php echo formatDate($apt['appointment_date']); ?> at 
                                    <?php echo formatTime($apt['appointment_time']); ?>
                                </small>
                            </div>
                            <div class="flex-shrink-0">
                                <span class="badge bg-primary status-badge">
                                    <?php echo ucfirst($apt['status']); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Appointment Status Distribution -->
<?php if (!empty($stats['by_status'])): ?>
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-pie me-2"></i>
                    Appointment Statistics
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($stats['by_status'] as $statusStat): ?>
                        <div class="col-md-3 mb-3">
                            <div class="text-center">
                                <div class="h2 mb-0 text-<?php 
                                    echo $statusStat['status'] === 'completed' ? 'success' : 
                                        ($statusStat['status'] === 'cancelled' ? 'danger' : 
                                        ($statusStat['status'] === 'confirmed' ? 'info' : 'primary')); 
                                ?>">
                                    <?php echo $statusStat['count']; ?>
                                </div>
                                <div class="text-muted">
                                    <?php echo ucfirst($statusStat['status']); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.timeline-item {
    position: relative;
}

.timeline-item:not(:last-child)::after {
    content: '';
    position: absolute;
    left: -2px;
    top: 100%;
    width: 4px;
    height: 20px;
    background-color: #dee2e6;
}
</style>

<?php require_once '../includes/footer.php'; ?>