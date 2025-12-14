// FIXED: Universal Notification System - Works on ALL pages
let notificationCheckInterval;
let dropdownVisible = false;

function initNotifications() {
    console.log('🔔 Initializing notification system...');
    
    const bell = document.getElementById('notificationBell');
    const dropdown = document.getElementById('notificationDropdown');
    
    if (!bell || !dropdown) {
        console.error('❌ Notification elements not found on this page');
        return;
    }
    
    console.log('✅ Notification elements found');
    
    // Check immediately on page load
    checkNotifications();
    
    // Then check every 30 seconds
    notificationCheckInterval = setInterval(checkNotifications, 30000);
    
    // Bell click handler
    bell.addEventListener('click', function(e) {
        e.stopPropagation();
        toggleNotificationDropdown();
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (dropdownVisible && !bell.contains(e.target) && !dropdown.contains(e.target)) {
            closeNotificationDropdown();
        }
    });
    
    console.log('✅ Notification system initialized successfully');
}

function checkNotifications() {
    // Determine correct API path based on current location
    const currentPath = window.location.pathname;
    let apiPath;
    
    if (currentPath.includes('/admin/')) {
        apiPath = '../api/get_notifications.php';
    } else if (currentPath.includes('/student/')) {
        apiPath = '../api/get_notifications.php';
    } else {
        apiPath = 'api/get_notifications.php';
    }
    
    fetch(apiPath)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                updateNotificationBell(data.count);
                updateNotificationDropdown(data.notifications);
            } else {
                console.error('Notification check failed:', data);
            }
        })
        .catch(error => {
            console.error('❌ Notification check error:', error);
        });
}

function updateNotificationBell(count) {
    const countElement = document.getElementById('notificationCount');
    
    if (countElement) {
        if (count > 0) {
            countElement.textContent = count > 99 ? '99+' : count;
            countElement.classList.add('show');
        } else {
            countElement.classList.remove('show');
        }
    }
}

function updateNotificationDropdown(notifications) {
    const dropdown = document.getElementById('notificationDropdown');
    
    if (!dropdown) return;
    
    if (!notifications || notifications.length === 0) {
        dropdown.innerHTML = `
            <div class="notification-header">Notifications</div>
            <div class="notification-empty">No new notifications</div>
        `;
        return;
    }
    
    let html = '<div class="notification-header">Notifications</div>';
    html += '<div class="notification-body">';
    
    notifications.forEach(notif => {
        const timeAgo = getTimeAgo(notif.created_at);
        const link = notif.link ? `onclick="handleNotificationClick(${notif.id}, '${notif.link}')"` : '';
        const cursor = notif.link ? 'cursor: pointer;' : '';
        
        html += `
            <div class="notification-item unread" ${link} style="${cursor}">
                <div class="notification-title">${escapeHtml(notif.title)}</div>
                <div class="notification-message">${escapeHtml(notif.message)}</div>
                <div class="notification-time">${timeAgo}</div>
            </div>
        `;
    });
    
    html += '</div>';
    html += '<div class="notification-footer"><button onclick="markAllAsRead()">Mark all as read</button></div>';
    
    dropdown.innerHTML = html;
}

function toggleNotificationDropdown() {
    const dropdown = document.getElementById('notificationDropdown');
    const bell = document.getElementById('notificationBell');
    
    if (!dropdown || !bell) return;
    
    if (dropdownVisible) {
        closeNotificationDropdown();
    } else {
        openNotificationDropdown();
    }
}

function openNotificationDropdown() {
    const dropdown = document.getElementById('notificationDropdown');
    const bell = document.getElementById('notificationBell');
    
    if (!dropdown || !bell) return;
    
    // Position dropdown below bell
    const bellRect = bell.getBoundingClientRect();
    dropdown.style.position = 'fixed';
    dropdown.style.top = (bellRect.bottom + 10) + 'px';
    dropdown.style.right = '20px';
    
    dropdown.classList.add('show');
    dropdownVisible = true;
}

function closeNotificationDropdown() {
    const dropdown = document.getElementById('notificationDropdown');
    if (dropdown) {
        dropdown.classList.remove('show');
        dropdownVisible = false;
    }
}

function handleNotificationClick(notificationId, link) {
    // Determine correct API path
    const currentPath = window.location.pathname;
    let apiPath;
    
    if (currentPath.includes('/admin/')) {
        apiPath = '../api/mark_notification_read.php';
    } else if (currentPath.includes('/student/')) {
        apiPath = '../api/mark_notification_read.php';
    } else {
        apiPath = 'api/mark_notification_read.php';
    }
    
    fetch(`${apiPath}?id=${notificationId}`)
        .then(() => {
            checkNotifications();
            if (link) {
                // FIXED: Smart path resolution
                // Get the base URL (protocol + domain + port)
                const baseUrl = window.location.origin;
                
                // Extract the project root (everything before /admin/ or /student/)
                let projectRoot = window.location.pathname;
                if (projectRoot.includes('/admin/')) {
                    projectRoot = projectRoot.substring(0, projectRoot.indexOf('/admin/'));
                } else if (projectRoot.includes('/student/')) {
                    projectRoot = projectRoot.substring(0, projectRoot.indexOf('/student/'));
                } else {
                    // We're at root level
                    projectRoot = projectRoot.substring(0, projectRoot.lastIndexOf('/'));
                }
                
                // Build the complete URL
                const fullUrl = baseUrl + projectRoot + '/' + link;
                
                console.log('Navigating to:', fullUrl);
                window.location.href = fullUrl;
            }
        })
        .catch(error => console.error('Error marking notification as read:', error));
}

function markAllAsRead() {
    // Determine correct API path
    const currentPath = window.location.pathname;
    let apiPath;
    
    if (currentPath.includes('/admin/')) {
        apiPath = '../api/get_notifications.php';
    } else if (currentPath.includes('/student/')) {
        apiPath = '../api/get_notifications.php';
    } else {
        apiPath = 'api/get_notifications.php';
    }
    
    fetch(`${apiPath}?mark_read=1`)
        .then(() => {
            checkNotifications();
            closeNotificationDropdown();
        })
        .catch(error => console.error('Error marking all as read:', error));
}

function getTimeAgo(timestamp) {
    const now = new Date();
    const time = new Date(timestamp);
    const diff = Math.floor((now - time) / 1000);
    
    if (diff < 60) return 'Just now';
    if (diff < 3600) return Math.floor(diff / 60) + ' min ago';
    if (diff < 86400) return Math.floor(diff / 3600) + ' hr ago';
    if (diff < 604800) return Math.floor(diff / 86400) + ' days ago';
    return 'Long ago';
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initNotifications);
} else {
    initNotifications();
}

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (notificationCheckInterval) {
        clearInterval(notificationCheckInterval);
    }
});