<?php
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if user is admin
if ($_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

// Get filter parameters
$filter_user = isset($_GET['user']) ? intval($_GET['user']) : null;
$filter_type = isset($_GET['type']) ? $_GET['type'] : null;

// Get active sessions
$active_sessions = getActiveSessions();

// Get activity log
$activity_log = getActivityLog($filter_user, 100);

// Get all users for filter dropdown
$users = getAllUsers();

include 'header.php';
?>

<h1>📊 User Activity Report</h1>

<!-- Active Sessions -->
<div class="card" style="margin-bottom: 30px;">
    <h2 style="margin-bottom: 20px;">🟢 Active Sessions (<?php echo count($active_sessions); ?>)</h2>
    
    <?php if (count($active_sessions) > 0): ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Login Time</th>
                        <th>Last Activity</th>
                        <th>Duration</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($active_sessions as $session): ?>
                    <?php
                        // Use PHP's date() function like in Sales History - it uses system timezone
                        $login = new DateTime($session['login_time']);
                        $last = new DateTime($session['last_activity']);
                        $now = new DateTime();
                        $duration = $login->diff($now);
                        $idle = $last->diff($now);
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($session['full_name']); ?></strong></td>
                        <td><span class="badge badge-<?php echo $session['role'] === 'admin' ? 'danger' : 'primary'; ?>"><?php echo ucfirst($session['role']); ?></span></td>
                        <td><?php echo date('d M Y, h:i A', strtotime($session['login_time'])); ?></td>
                        <td>
                            <?php 
                            if ($idle->i < 5 && $idle->h == 0) {
                                echo '<span class="badge badge-success">Active now</span>';
                            } else if ($idle->h > 0) {
                                echo $idle->h . 'h ' . $idle->i . 'm ago';
                            } else {
                                echo $idle->i . ' min ago';
                            }
                            ?>
                        </td>
                        <td><?php echo $duration->h . 'h ' . $duration->i . 'm'; ?></td>
                        <td><?php echo htmlspecialchars($session['ip_address']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p style="text-align: center; color: #999; padding: 20px;">No active sessions</p>
    <?php endif; ?>
</div>

<!-- Activity Log -->
<div class="card">
    <h2 style="margin-bottom: 20px;">📜 Activity Log</h2>
    
    <!-- Filters -->
    <div style="margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
        <form method="GET" action="" style="display: flex; gap: 10px; flex-wrap: wrap;">
            <select name="user" class="form-control" style="width: auto;">
                <option value="">All Users</option>
                <?php foreach ($users as $user): ?>
                    <option value="<?php echo $user['id']; ?>" <?php echo $filter_user == $user['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($user['full_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <select name="type" class="form-control" style="width: auto;">
                <option value="">All Activities</option>
                <option value="login" <?php echo $filter_type == 'login' ? 'selected' : ''; ?>>Login</option>
                <option value="logout" <?php echo $filter_type == 'logout' ? 'selected' : ''; ?>>Logout</option>
                <option value="sale" <?php echo $filter_type == 'sale' ? 'selected' : ''; ?>>Sales</option>
                <option value="product_add" <?php echo $filter_type == 'product_add' ? 'selected' : ''; ?>>Product Add</option>
                <option value="product_edit" <?php echo $filter_type == 'product_edit' ? 'selected' : ''; ?>>Product Edit</option>
                <option value="product_delete" <?php echo $filter_type == 'product_delete' ? 'selected' : ''; ?>>Product Delete</option>
            </select>
            
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="activity_report.php" class="btn btn-secondary">Clear</a>
        </form>
    </div>
    
    <?php if (count($activity_log) > 0): ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>User</th>
                        <th>Activity</th>
                        <th>Description</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($activity_log as $log): ?>
                        <?php
                        // Skip if filters don't match
                        if ($filter_user && $log['user_id'] != $filter_user) continue;
                        if ($filter_type && $log['activity_type'] != $filter_type) continue;
                        
                        // Activity type badges
                        $badge_class = 'secondary';
                        $icon = '📄';
                        switch ($log['activity_type']) {
                            case 'login':
                                $badge_class = 'success';
                                $icon = '🔓';
                                break;
                            case 'logout':
                                $badge_class = 'danger';
                                $icon = '🔒';
                                break;
                            case 'sale':
                                $badge_class = 'primary';
                                $icon = '💰';
                                break;
                            case 'product_add':
                            case 'product_edit':
                            case 'product_delete':
                                $badge_class = 'warning';
                                $icon = '📦';
                                break;
                            case 'inventory_receive':
                                $badge_class = 'info';
                                $icon = '📥';
                                break;
                        }
                        ?>
                        <tr>
                            <td><?php echo date('d M, h:i A', strtotime($log['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($log['username']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $badge_class; ?>">
                                    <?php echo $icon . ' ' . ucwords(str_replace('_', ' ', $log['activity_type'])); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($log['description']); ?></td>
                            <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p style="text-align: center; color: #999; padding: 20px;">No activity logs found</p>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>