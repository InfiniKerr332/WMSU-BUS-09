// Auto-close alert messages after 7 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    
    alerts.forEach(function(alert) {
        // Make sure alert is visible
        alert.style.display = 'block';
        alert.style.opacity = '1';
        
        // Auto-close after 7 seconds
        setTimeout(function() {
            alert.style.transition = 'opacity 0.3s ease';
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.style.display = 'none';
            }, 300);
        }, 7000);
    });
    
    // Alert close button handler
    const closeButtons = document.querySelectorAll('.alert-close');
    closeButtons.forEach(function(btn) {
        btn.style.cursor = 'pointer';
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const alert = this.closest('.alert');
            if (alert) {
                alert.style.transition = 'opacity 0.3s ease';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 300);
            }
        });
    });
    
    // Scroll to first alert if present
    const firstAlert = document.querySelector('.alert');
    if (firstAlert) {
        firstAlert.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
});

// Show loading spinner
function showLoading() {
    const loadingDiv = document.createElement('div');
    loadingDiv.className = 'loading';
    loadingDiv.id = 'loading-spinner';
    loadingDiv.style.position = 'fixed';
    loadingDiv.style.top = '50%';
    loadingDiv.style.left = '50%';
    loadingDiv.style.transform = 'translate(-50%, -50%)';
    loadingDiv.style.zIndex = '9999';
    document.body.appendChild(loadingDiv);
}

// Hide loading spinner
function hideLoading() {
    const loadingDiv = document.getElementById('loading-spinner');
    if (loadingDiv) {
        loadingDiv.remove();
    }
}

// Format date input to prevent past dates
function setMinDate(inputId) {
    const input = document.getElementById(inputId);
    if (input) {
        const today = new Date().toISOString().split('T')[0];
        input.setAttribute('min', today);
    }
}

// Disable Sundays in date picker
function disableSundays(inputId) {
    const input = document.getElementById(inputId);
    if (input) {
        input.addEventListener('input', function() {
            const date = new Date(this.value);
            if (date.getDay() === 0) {
                alert('Sorry, reservations on Sundays are not allowed. Please choose a working day (Monday-Saturday).');
                this.value = '';
            }
        });
    }
}

// Show success message
function showSuccess(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-success';
    alertDiv.style.position = 'fixed';
    alertDiv.style.top = '20px';
    alertDiv.style.left = '50%';
    alertDiv.style.transform = 'translateX(-50%)';
    alertDiv.style.zIndex = '10000';
    alertDiv.style.maxWidth = '500px';
    alertDiv.style.padding = '20px';
    alertDiv.innerHTML = message + '<span class="alert-close" style="cursor: pointer; float: right; font-size: 20px;">&times;</span>';
    
    document.body.appendChild(alertDiv);
    
    alertDiv.querySelector('.alert-close').addEventListener('click', function() {
        alertDiv.style.opacity = '0';
        setTimeout(() => alertDiv.remove(), 300);
    });
    
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.style.opacity = '0';
            setTimeout(() => alertDiv.remove(), 300);
        }
    }, 5000);
}

// Show error message
function showError(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-error';
    alertDiv.style.position = 'fixed';
    alertDiv.style.top = '20px';
    alertDiv.style.left = '50%';
    alertDiv.style.transform = 'translateX(-50%)';
    alertDiv.style.zIndex = '10000';
    alertDiv.style.maxWidth = '500px';
    alertDiv.style.padding = '20px';
    alertDiv.innerHTML = message + '<span class="alert-close" style="cursor: pointer; float: right; font-size: 20px;">&times;</span>';
    
    document.body.appendChild(alertDiv);
    
    alertDiv.querySelector('.alert-close').addEventListener('click', function() {
        alertDiv.style.opacity = '0';
        setTimeout(() => alertDiv.remove(), 300);
    });
    
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.style.opacity = '0';
            setTimeout(() => alertDiv.remove(), 300);
        }
    }, 7000);
}