<?php
/**
 * Footer Template
 * Shared footer component
 */
?>
                    </div> <!-- End main-content -->
                <?php if ($isLoggedIn && !isset($hideLayout)): ?>
                </div> <!-- End col -->
            </div> <!-- End row -->
        <?php endif; ?>
    </div> <!-- End container-fluid -->
    
    <!-- Footer -->
    <footer class="footer bg-white mt-auto">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0 text-muted">
                        &copy; <?php echo date('Y'); ?> Medical Appointment System. All rights reserved.
                    </p>
                </div>
                <div class="col-md-6 text-end">
                    <p class="mb-0 text-muted">
                        <small>Secure • Reliable • Professional</small>
                    </p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
        
        // Form validation helper
        function validateForm(formId) {
            const form = document.getElementById(formId);
            if (!form) return false;
            
            const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
            let isValid = true;
            
            inputs.forEach(function(input) {
                if (!input.value.trim()) {
                    input.classList.add('is-invalid');
                    isValid = false;
                } else {
                    input.classList.remove('is-invalid');
                }
            });
            
            return isValid;
        }
        
        // CSRF token helper
        function getCSRFToken() {
            const token = document.querySelector('input[name="csrf_token"]');
            return token ? token.value : '';
        }
        
        // Confirmation dialog helper
        function confirmAction(message) {
            return confirm(message || 'Are you sure you want to perform this action?');
        }
        
        // Date formatting helper
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }
        
        // Time formatting helper
        function formatTime(timeString) {
            const time = new Date('1970-01-01T' + timeString);
            return time.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
        }
    </script>
    
    <?php if (isset($additionalJS)): ?>
        <?php echo $additionalJS; ?>
    <?php endif; ?>
</body>
</html>