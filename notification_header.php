<?php
// Notification Bell Component
// Include this in the header of all pages that need notifications
?>
<link rel="stylesheet" href="<?php echo SITE_URL; ?>css/notifications.css">

<!-- Notification Bell -->
<div class="notification-bell" id="notificationBell">
    <span class="notification-bell-icon">🔔</span>
    <span class="notification-count" id="notificationCount">0</span>
</div>

<!-- Notification Dropdown -->
<div class="notification-dropdown" id="notificationDropdown">
    <div class="notification-header">Notifications</div>
    <div class="notification-empty">Loading...</div>
</div>

<script src="<?php echo SITE_URL; ?>js/notifications.js"></script>