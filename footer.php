</main>
    </div>
    
    <!-- Notification Script -->
    <script>
    var notificationsPanelOpen = false;
    
    // Check notifications every 10 seconds
    setInterval(function() {
        fetchNotifications(false);
    }, 10000);
    
    // Initial load
    fetchNotifications(false);
    
    function fetchNotifications(showPanel) {
        fetch('notifications.php?action=get_notifications')
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    var badge = document.getElementById('notificationBadge');
                    
                    if (data.unread_count > 0) {
                        badge.textContent = data.unread_count;
                        badge.style.display = 'inline-block';
                    } else {
                        badge.style.display = 'none';
                    }
                    
                    if (showPanel) {
                        displayNotifications(data.notifications);
                    }
                }
            })
            .catch(function(error) {
                console.error('Error fetching notifications:', error);
            });
    }
    
    function toggleNotifications() {
        var panel = document.getElementById('notificationPanel');
        
        if (notificationsPanelOpen) {
            panel.style.display = 'none';
            notificationsPanelOpen = false;
        } else {
            panel.style.display = 'flex';
            notificationsPanelOpen = true;
            fetchNotifications(true);
        }
    }
    
    function displayNotifications(notifications) {
        var list = document.getElementById('notificationList');
        
        if (notifications.length === 0) {
            list.innerHTML = '<p style="text-align: center; padding: 40px; color: var(--secondary);">No notifications</p>';
            return;
        }
        
        var html = '';
        notifications.forEach(function(notif) {
            var unreadClass = notif.is_read == 0 ? 'unread' : '';
            var timeAgo = getTimeAgo(notif.created_at);
            
            html += '<div class="notification-item ' + unreadClass + ' type-' + notif.type + '" onclick="handleNotificationClick(' + notif.id + ', \'' + notif.link + '\')">';
            html += '<div class="notification-title">' + notif.title + '</div>';
            html += '<div class="notification-message">' + notif.message + '</div>';
            html += '<div class="notification-time">' + timeAgo + '</div>';
            html += '</div>';
        });
        
        list.innerHTML = html;
    }
    
    function handleNotificationClick(notifId, link) {
        markAsRead(notifId);
        
        if (link) {
            window.location.href = link;
        }
    }
    
    function markAsRead(notifId) {
        var formData = new FormData();
        formData.append('notification_id', notifId);
        
        fetch('notifications.php?action=mark_read', {
            method: 'POST',
            body: formData
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                fetchNotifications(true);
            }
        });
    }
    
    function markAllAsRead() {
        fetch('notifications.php?action=mark_all_read', {
            method: 'POST'
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                fetchNotifications(true);
            }
        });
    }
    
    function getTimeAgo(dateString) {
        var date = new Date(dateString);
        var now = new Date();
        var seconds = Math.floor((now - date) / 1000);
        
        if (seconds < 60) return 'Just now';
        if (seconds < 3600) return Math.floor(seconds / 60) + ' minutes ago';
        if (seconds < 86400) return Math.floor(seconds / 3600) + ' hours ago';
        if (seconds < 604800) return Math.floor(seconds / 86400) + ' days ago';
        
        return date.toLocaleDateString();
    }
    
    // Close notifications when clicking outside
    document.addEventListener('click', function(event) {
        var panel = document.getElementById('notificationPanel');
        var trigger = event.target.closest('a[href="#"]');
        
        if (!panel.contains(event.target) && !trigger && notificationsPanelOpen) {
            panel.style.display = 'none';
            notificationsPanelOpen = false;
        }
    });
    </script>
</body>
</html>