/* Main JavaScript for Medical Appointment System */

document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            if (typeof bootstrap !== 'undefined') {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
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

// View toggle functionality for appointments
function initializeViewToggle() {
    const listViewRadio = document.getElementById('listView');
    const cardViewRadio = document.getElementById('cardView');
    const listViewContent = document.getElementById('listViewContent');
    const cardViewContent = document.getElementById('cardViewContent');
    
    if (listViewRadio && cardViewRadio && listViewContent && cardViewContent) {
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
    }
}

// Date range validation
function initializeDateValidation() {
    const dateFromInput = document.getElementById('date_from');
    const dateToInput = document.getElementById('date_to');
    
    if (dateFromInput && dateToInput) {
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
    }
}

// Initialize all components
document.addEventListener('DOMContentLoaded', function() {
    initializeViewToggle();
    initializeDateValidation();
});