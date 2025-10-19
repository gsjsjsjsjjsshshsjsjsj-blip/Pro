<?php
/**
 * Patient Dashboard
 * Overview of appointments, quick actions, and system navigation
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

// Get patient's upcoming appointments
$upcomingAppointments = $appointment->getAppointments([
    'patient_id' => $_SESSION['user_id'],
    'date_from' => date('Y-m-d'),
    'status' => 'scheduled'
], 5);

// Get recent appointments
$recentAppointments = $appointment->getAppointments([
    'patient_id' => $_SESSION['user_id']
], 5);

// Get appointment statistics
$stats = $appointment->getStatistics([
    'patient_id' => $_SESSION['user_id']
]);

// Get available doctors count
$availableDoctors = $doctor->getAllDoctors(['is_available' => 1], 10);

$pageTitle = 'Patient Dashboard - Medical Appointment System';
?>

<?php require_once '../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h2 mb-1">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
        <p class="text-muted mb-0">Manage your appointments and healthcare needs</p>
    </div>
    <div>
        <a href="book-appointment.php" class="btn btn-primary">
            <i class="fas fa-calendar-plus me-2"></i>
            Book Appointment
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
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-0">Available Doctors</h6>
                        <h2 class="mb-0"><?php echo count($availableDoctors); ?></h2>
                    </div>
                    <div class="opacity-75">
                        <i class="fas fa-user-md fa-2x"></i>
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
                        <h6 class="card-title mb-0">This Month</h6>
                        <h2 class="mb-0">
                            <?php 
                            $thisMonthStats = $appointment->getStatistics([
                                'patient_id' => $_SESSION['user_id'],
                                'date_from' => date('Y-m-01'),
                                'date_to' => date('Y-m-t')
                            ]);
                            echo $thisMonthStats['total'];
                            ?>
                        </h2>
                    </div>
                    <div class="opacity-75">
                        <i class="fas fa-calendar-alt fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Upcoming Appointments -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-calendar-check me-2"></i>
                    Upcoming Appointments
                </h5>
                <a href="appointments.php" class="btn btn-sm btn-outline-primary">
                    View All
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($upcomingAppointments)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">No upcoming appointments</h6>
                        <p class="text-muted mb-3">Book your next appointment to get started</p>
                        <a href="book-appointment.php" class="btn btn-primary">
                            <i class="fas fa-calendar-plus me-2"></i>
                            Book Appointment
                        </a>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($upcomingAppointments as $apt): ?>
                            <div class="list-group-item appointment-card">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center mb-2">
                                            <h6 class="mb-0 me-3">
                                                Dr. <?php echo htmlspecialchars($apt['doctor_first_name'] . ' ' . $apt['doctor_last_name']); ?>
                                            </h6>
                                            <span class="badge bg-primary status-badge">
                                                <?php echo ucfirst($apt['status']); ?>
                                            </span>
                                        </div>
                                        <p class="text-muted mb-1">
                                            <i class="fas fa-stethoscope me-2"></i>
                                            <?php echo htmlspecialchars($apt['specialization']); ?>
                                        </p>
                                        <p class="text-muted mb-1">
                                            <i class="fas fa-calendar me-2"></i>
                                            <?php echo formatDate($apt['appointment_date']); ?>
                                            <i class="fas fa-clock ms-3 me-2"></i>
                                            <?php echo formatTime($apt['appointment_time']); ?>
                                        </p>
                                        <?php if (!empty($apt['reason'])): ?>
                                            <p class="text-muted mb-0">
                                                <i class="fas fa-comment me-2"></i>
                                                <?php echo htmlspecialchars($apt['reason']); ?>
                                            </p>
                                        <?php endif; ?>
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
                                                    <a class="dropdown-item text-danger" 
                                                       href="cancel-appointment.php?id=<?php echo $apt['id']; ?>"
                                                       onclick="return confirmAction('Are you sure you want to cancel this appointment?')">
                                                        <i class="fas fa-times me-2"></i>Cancel
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions & Available Doctors -->
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
                    <a href="book-appointment.php" class="btn btn-primary">
                        <i class="fas fa-calendar-plus me-2"></i>
                        Book New Appointment
                    </a>
                    <a href="appointments.php" class="btn btn-outline-primary">
                        <i class="fas fa-calendar-check me-2"></i>
                        View All Appointments
                    </a>
                    <a href="doctors.php" class="btn btn-outline-info">
                        <i class="fas fa-user-md me-2"></i>
                        Browse Doctors
                    </a>
                    <a href="profile.php" class="btn btn-outline-secondary">
                        <i class="fas fa-user me-2"></i>
                        Update Profile
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Available Doctors -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user-md me-2"></i>
                    Available Doctors
                </h5>
                <a href="doctors.php" class="btn btn-sm btn-outline-primary">
                    View All
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($availableDoctors)): ?>
                    <p class="text-muted text-center">No doctors available at the moment.</p>
                <?php else: ?>
                    <?php foreach (array_slice($availableDoctors, 0, 3) as $doc): ?>
                        <div class="d-flex align-items-center mb-3">
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
                                <small class="text-muted"><?php echo htmlspecialchars($doc['specialization']); ?></small>
                            </div>
                            <div class="flex-shrink-0">
                                <a href="book-appointment.php?doctor_id=<?php echo $doc['id']; ?>" 
                                   class="btn btn-sm btn-outline-primary">
                                    Book
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<?php if (!empty($recentAppointments)): ?>
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-history me-2"></i>
                    Recent Appointments
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Doctor</th>
                                <th>Specialization</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($recentAppointments, 0, 5) as $apt): ?>
                                <tr>
                                    <td>
                                        Dr. <?php echo htmlspecialchars($apt['doctor_first_name'] . ' ' . $apt['doctor_last_name']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($apt['specialization']); ?></td>
                                    <td>
                                        <?php echo formatDate($apt['appointment_date']); ?><br>
                                        <small class="text-muted"><?php echo formatTime($apt['appointment_time']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $apt['status'] === 'completed' ? 'success' : 
                                                ($apt['status'] === 'cancelled' ? 'danger' : 
                                                ($apt['status'] === 'confirmed' ? 'info' : 'primary')); 
                                        ?> status-badge">
                                            <?php echo ucfirst($apt['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="appointment-details.php?id=<?php echo $apt['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>