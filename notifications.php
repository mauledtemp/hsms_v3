<?php
require_once 'config.php';
require_once 'functions.php';
requireLogin();

header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'get_notifications':
        getNotifications();
        break;
    
    case 'mark_read':
        markNotificationRead();
        break;
    
    case 'mark_all_read':
        markAllNotificationsRead();
        break;
    
    default:
        echo json_encode(['error' => 'Invalid action']);
}

function getNotifications() {
    $conn = getDBConnection();
    $user_id = $_SESSION['user_id'];
    
    // Get unread count
    $unread_query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
    $stmt = $conn->prepare($unread_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $unread_count = $result->fetch_assoc()['count'];
    
    // Get recent notifications (last 20)
    $notifications_query = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20";
    $stmt = $conn->prepare($notifications_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'unread_count' => $unread_count,
        'notifications' => $notifications
    ]);
}

function markNotificationRead() {
    $conn = getDBConnection();
    $notification_id = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;
    $user_id = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notification_id, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to mark as read']);
    }
}

function markAllNotificationsRead() {
    $conn = getDBConnection();
    $user_id = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to mark all as read']);
    }
}
?>