<?php
/**
 * Patient Appointments Page
 * View and manage all patient appointments
 */

require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../classes/RoleManager.php';
require_once '../classes/Appointment.php';

// Require patient role
$database = new Database();
$db = $database->getConnection();
$roleManager = new RoleManager($db);
$roleManager->requireRoleOrHigher('patient');

$appointment = new Appointment($db);

// Handle filters
$filters = [
    'patient_id' => $_SESSION['user_id'],
    'status' => $_GET['status'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? ''
];

// Remove empty filters
$filters = array_filter($filters, function($value) {
    return $value !== '';
});

// Get appointments
$appointments = $appointment->getAppointments($filters, 50);

// Get appointment statistics
$stats = $appointment->getStatistics(['patient_id' => $_SESSION['user_id']]);

$pageTitle = 'My Appointments - Medical Appointment System';
?>

<?php require_once '../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h2 mb-1">My Appointments</h1>
        <p class="text-muted mb-0">View and manage your medical appointments</p>
    </div>
    <div>
        <a href="book-appointment.php" class="btn btn-primary">
            <i class="fas fa-calendar-plus me-2"></i>
            Book New Appointment
        </a>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <i class="fas fa-calendar-check fa-2x mb-2"></i>
                <h3 class="mb-0"><?php echo $stats['total']; ?></h3>
                <small>Total Appointments</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <i class="fas fa-clock fa-2x mb-2"></i>
                <h3 class="mb-0"><?php echo $stats['upcoming']; ?></h3>
                <small>Upcoming</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <i class="fas fa-check-circle fa-2x mb-2"></i>
                <h3 class="mb-0">
                    <?php 
                    $completed = array_filter($stats['by_status'], function($status) {
                        return $status['status'] === 'completed';
                    });
                    echo !empty($completed) ? $completed[0]['count'] : 0;
                    ?>
                </h3>
                <small>Completed</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <i class="fas fa-times-circle fa-2x mb-2"></i>
                <h3 class="mb-0">
                    <?php 
                    $cancelled = array_filter($stats['by_status'], function($status) {
                        return $status['status'] === 'cancelled';
                    });
                    echo !empty($cancelled) ? $cancelled[0]['count'] : 0;
                    ?>
                </h3>
                <small>Cancelled</small>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="scheduled" <?php echo ($_GET['status'] ?? '') === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                    <option value="confirmed" <?php echo ($_GET['status'] ?? '') === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                    <option value="completed" <?php echo ($_GET['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo ($_GET['status'] ?? '') === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="date_from" class="form-label">From Date</label>
                <input type="date" class="form-control" id="date_from" name="date_from" 
                       value="<?php echo htmlspecialchars($_GET['date_from'] ?? ''); ?>">
            </div>
            
            <div class="col-md-3">
                <label for="date_to" class="form-label">To Date</label>
                <input type="date" class="form-control" id="date_to" name="date_to" 
                       value="<?php echo htmlspecialchars($_GET['date_to'] ?? ''); ?>">
            </div>
            
            <div class="col-md-3">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-2"></i>Filter
                    </button>
                    <a href="appointments.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-2"></i>Clear
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Appointments List -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i class="fas fa-list me-2"></i>
            Appointments
            <?php if (!empty($filters)): ?>
                <span class="badge bg-primary ms-2"><?php echo count($appointments); ?> results</span>
            <?php endif; ?>
        </h5>
        
        <div class="btn-group" role="group">
            <input type="radio" class="btn-check" name="view" id="listView" autocomplete="off" checked>
            <label class="btn btn-outline-primary" for="listView">
                <i class="fas fa-list"></i>
            </label>
            
            <input type="radio" class="btn-check" name="view" id="cardView" autocomplete="off">
            <label class="btn btn-outline-primary" for="cardView">
                <i class="fas fa-th-large"></i>
            </label>
        </div>
    </div>
    
    <div class="card-body">
        <?php if (empty($appointments)): ?>
            <div class="text-center py-5">
                <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">No appointments found</h5>
                <p class="text-muted">
                    <?php if (!empty($filters) && count($filters) > 1): ?>
                        Try adjusting your filters or <a href="appointments.php">view all appointments</a>.
                    <?php else: ?>
                        You haven't booked any appointments yet.
                    <?php endif; ?>
                </p>
                <a href="book-appointment.php" class="btn btn-primary">
                    <i class="fas fa-calendar-plus me-2"></i>
                    Book Your First Appointment
                </a>
            </div>
        <?php else: ?>
            <!-- List View -->
            <div id="listViewContent">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Doctor</th>
                                <th>Specialization</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                                <th>Reason</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $apt): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" 
                                                 style="width: 35px; height: 35px;">
                                                <i class="fas fa-user-md"></i>
                                            </div>
                                            <div>
                                                <strong>Dr. <?php echo htmlspecialchars($apt['doctor_first_name'] . ' ' . $apt['doctor_last_name']); ?></strong>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($apt['specialization']); ?></td>
                                    <td>
                                        <div>
                                            <strong><?php echo formatDate($apt['appointment_date']); ?></strong><br>
                                            <small class="text-muted"><?php echo formatTime($apt['appointment_time']); ?></small>
                                        </div>
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
                                        <?php if (!empty($apt['reason'])): ?>
                                            <span class="text-truncate d-inline-block" style="max-width: 150px;" 
                                                  title="<?php echo htmlspecialchars($apt['reason']); ?>">
                                                <?php echo htmlspecialchars($apt['reason']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
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
                                                <?php if ($apt['status'] === 'scheduled' && strtotime($apt['appointment_date']) > time()): ?>
                                                    <li><hr class="dropdown-divider"></li>
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
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Card View -->
            <div id="cardViewContent" class="d-none">
                <div class="row g-4">
                    <?php foreach ($appointments as $apt): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card appointment-card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" 
                                                 style="width: 40px; height: 40px;">
                                                <i class="fas fa-user-md"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">
                                                    Dr. <?php echo htmlspecialchars($apt['doctor_first_name'] . ' ' . $apt['doctor_last_name']); ?>
                                                </h6>
                                                <small class="text-muted"><?php echo htmlspecialchars($apt['specialization']); ?></small>
                                            </div>
                                        </div>
                                        <span class="badge bg-<?php 
                                            echo $apt['status'] === 'completed' ? 'success' : 
                                                ($apt['status'] === 'cancelled' ? 'danger' : 
                                                ($apt['status'] === 'confirmed' ? 'info' : 'primary')); 
                                        ?> status-badge">
                                            <?php echo ucfirst($apt['status']); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-calendar text-primary me-2"></i>
                                            <strong><?php echo formatDate($apt['appointment_date']); ?></strong>
                                        </div>
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-clock text-primary me-2"></i>
                                            <?php echo formatTime($apt['appointment_time']); ?>
                                        </div>
                                        <?php if (!empty($apt['reason'])): ?>
                                            <div class="d-flex align-items-start">
                                                <i class="fas fa-comment text-primary me-2 mt-1"></i>
                                                <small class="text-muted"><?php echo htmlspecialchars($apt['reason']); ?></small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="d-flex gap-2">
                                        <a href="appointment-details.php?id=<?php echo $apt['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary flex-fill">
                                            <i class="fas fa-eye me-1"></i>Details
                                        </a>
                                        <?php if ($apt['status'] === 'scheduled' && strtotime($apt['appointment_date']) > time()): ?>
                                            <a href="cancel-appointment.php?id=<?php echo $apt['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirmAction('Are you sure you want to cancel this appointment?')">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // View toggle functionality
    const listViewRadio = document.getElementById('listView');
    const cardViewRadio = document.getElementById('cardView');
    const listViewContent = document.getElementById('listViewContent');
    const cardViewContent = document.getElementById('cardViewContent');
    
    listViewRadio.addEventListener('change', function() {
        if (this.checked) {
            listViewContent.classList.remove('d-none');
            cardViewContent.classList.add('d-none');
        }
    });
    
    cardViewRadio.addEventListener('change', function() {
        if (this.checked) {
            listViewContent.classList.add('d-none');
            cardViewContent.classList.remove('d-none');
        }
    });
    
    // Date range validation
    const dateFromInput = document.getElementById('date_from');
    const dateToInput = document.getElementById('date_to');
    
    dateFromInput.addEventListener('change', function() {
        if (this.value && dateToInput.value && this.value > dateToInput.value) {
            dateToInput.value = this.value;
        }
        dateToInput.min = this.value;
    });
    
    dateToInput.addEventListener('change', function() {
        if (this.value && dateFromInput.value && this.value < dateFromInput.value) {
            dateFromInput.value = this.value;
        }
        dateFromInput.max = this.value;
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>