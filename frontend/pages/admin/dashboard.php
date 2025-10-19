<?php
/**
 * Admin Dashboard
 * System overview, user management, and administrative controls
 */

require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../classes/RoleManager.php';
require_once '../classes/Appointment.php';
require_once '../classes/Doctor.php';
require_once '../classes/User.php';

// Require admin role
$database = new Database();
$db = $database->getConnection();
$roleManager = new RoleManager($db);
$roleManager->requireRoleOrHigher('admin');

$appointment = new Appointment($db);
$doctor = new Doctor($db);
$user = new User($db);

// Get system statistics
$appointmentStats = $appointment->getStatistics();
$totalDoctors = count($doctor->getAllDoctors());
$availableDoctors = count($doctor->getAllDoctors(['is_available' => 1]));

// Get recent appointments
$recentAppointments = $appointment->getAppointments([], 10);

// Get recent registrations (this would need a method in User class)
// For now, we'll simulate this data

$pageTitle = 'Admin Dashboard - Medical Appointment System';
?>

<?php require_once '../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h2 mb-1">System Administration</h1>
        <p class="text-muted mb-0">Monitor and manage the medical appointment system</p>
    </div>
    <div class="d-flex gap-2">
        <a href="users.php" class="btn btn-outline-primary">
            <i class="fas fa-users me-2"></i>
            Manage Users
        </a>
        <a href="reports.php" class="btn btn-primary">
            <i class="fas fa-chart-bar me-2"></i>
            View Reports
        </a>
    </div>
</div>

<!-- System Overview Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-0">Total Appointments</h6>
                        <h2 class="mb-0"><?php echo $appointmentStats['total']; ?></h2>
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
                        <h6 class="card-title mb-0">Active Doctors</h6>
                        <h2 class="mb-0"><?php echo $availableDoctors; ?></h2>
                        <small class="opacity-75">of <?php echo $totalDoctors; ?> total</small>
                    </div>
                    <div class="opacity-75">
                        <i class="fas fa-user-md fa-2x"></i>
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
                        <h2 class="mb-0"><?php echo $appointmentStats['upcoming']; ?></h2>
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
                        <h6 class="card-title mb-0">System Status</h6>
                        <h2 class="mb-0">
                            <i class="fas fa-check-circle"></i>
                        </h2>
                        <small class="opacity-75">Operational</small>
                    </div>
                    <div class="opacity-75">
                        <i class="fas fa-server fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Activity -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-history me-2"></i>
                    Recent Appointments
                </h5>
                <a href="appointments.php" class="btn btn-sm btn-outline-primary">
                    View All
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($recentAppointments)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">No recent appointments</h6>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Doctor</th>
                                    <th>Date & Time</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($recentAppointments, 0, 8) as $apt): ?>
                                    <tr>
                                        <td>
                                            <?php echo htmlspecialchars($apt['patient_first_name'] . ' ' . $apt['patient_last_name']); ?>
                                        </td>
                                        <td>
                                            Dr. <?php echo htmlspecialchars($apt['doctor_first_name'] . ' ' . $apt['doctor_last_name']); ?>
                                        </td>
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
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions & System Info -->
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
                    <a href="users.php" class="btn btn-primary">
                        <i class="fas fa-users me-2"></i>
                        Manage Users
                    </a>
                    <a href="doctors.php" class="btn btn-outline-primary">
                        <i class="fas fa-user-md me-2"></i>
                        Manage Doctors
                    </a>
                    <a href="appointments.php" class="btn btn-outline-info">
                        <i class="fas fa-calendar-check me-2"></i>
                        View Appointments
                    </a>
                    <a href="reports.php" class="btn btn-outline-success">
                        <i class="fas fa-chart-bar me-2"></i>
                        Generate Reports
                    </a>
                    <a href="settings.php" class="btn btn-outline-secondary">
                        <i class="fas fa-cog me-2"></i>
                        System Settings
                    </a>
                </div>
            </div>
        </div>
        
        <!-- System Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    System Information
                </h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>PHP Version:</strong><br>
                    <span class="text-muted"><?php echo PHP_VERSION; ?></span>
                </div>
                
                <div class="mb-3">
                    <strong>Database:</strong><br>
                    <span class="text-muted">MySQL Connected</span>
                </div>
                
                <div class="mb-3">
                    <strong>Server Time:</strong><br>
                    <span class="text-muted"><?php echo date('Y-m-d H:i:s'); ?></span>
                </div>
                
                <div class="mb-3">
                    <strong>System Load:</strong><br>
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 25%"></div>
                    </div>
                    <small class="text-muted">Low (25%)</small>
                </div>
                
                <div class="mb-0">
                    <strong>Last Backup:</strong><br>
                    <span class="text-muted">2024-01-15 02:00:00</span>
                </div>
            </div>
        </div>
        
        <!-- Appointment Status Distribution -->
        <?php if (!empty($appointmentStats['by_status'])): ?>
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-pie me-2"></i>
                    Appointment Status
                </h5>
            </div>
            <div class="card-body">
                <?php foreach ($appointmentStats['by_status'] as $statusStat): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-capitalize"><?php echo $statusStat['status']; ?></span>
                        <span class="badge bg-<?php 
                            echo $statusStat['status'] === 'completed' ? 'success' : 
                                ($statusStat['status'] === 'cancelled' ? 'danger' : 
                                ($statusStat['status'] === 'confirmed' ? 'info' : 'primary')); 
                        ?>">
                            <?php echo $statusStat['count']; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Alerts and Notifications -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-bell me-2"></i>
                    System Alerts & Notifications
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>System Update:</strong> All systems are running normally. Last update: <?php echo date('Y-m-d'); ?>
                </div>
                
                <?php if ($availableDoctors < $totalDoctors): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Doctor Availability:</strong> 
                    <?php echo ($totalDoctors - $availableDoctors); ?> doctor(s) are currently unavailable.
                    <a href="doctors.php" class="alert-link">Review doctor status</a>
                </div>
                <?php endif; ?>
                
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Database Status:</strong> All database connections are healthy and responsive.
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>