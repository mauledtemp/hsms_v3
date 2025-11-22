<?php
$page_title = 'Activity Report';
include 'header.php';
requireAdmin();

$filter_user = isset($_GET['user']) ? intval($_GET['user']) : null;
$filter_type = isset($_GET['type']) ? sanitizeInput($_GET['type']) : null;

$activities = getActivityLog($filter_user, 200);
$active_sessions = getActiveSessions();
$all_users = getAllUsers();
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
                        $login = new DateTime($session['login_time']);
                        $last = new DateTime($session['last_activity']);
                        $now = new DateTime();
                        $duration = $login->diff($now);
                        $idle = $last->diff($now);
                    ?>
                    <tr>
                        <td><strong><?php echo $session['full_name']; ?></strong></td>
                        <td><span class="badge badge-<?php echo $session['role'] === 'admin' ? 'danger' : 'primary'; ?>"><?php echo ucfirst($session['role']); ?></span></td>
                        <td><?php echo date('d M Y, h:i A', strtotime($session['login_time'])); ?></td>
                        <td>
                            <?php 
                            if ($idle->i < 5) {
                                echo '<span class="badge badge-success">Active now</span>';
                            } else {
                                echo $idle->i . ' min ago';
                            }
                            ?>
                        </td>
                        <td><?php echo $duration->h . 'h ' . $duration->i . 'm'; ?></td>
                        <td><?php echo $session['ip_address']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p style="text-align: center; padding: 40px; color: var(--secondary);">No active sessions</p>
    <?php endif; ?>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom: 30px;">
    <h2 style="margin-bottom: 20px;">🔍 Filter Activity</h2>
    
    <form method="GET" style="display: flex; gap: 15px; align-items: end;">
        <div class="form-group" style="flex: 1; margin-bottom: 0;">
            <label>User</label>
            <select name="user" class="form-control">
                <option value="">All Users</option>
                <?php foreach ($all_users as $user): ?>
                    <option value="<?php echo $user['id']; ?>" <?php echo ($filter_user == $user['id']) ? 'selected' : ''; ?>>
                        <?php echo $user['full_name']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group" style="flex: 1; margin-bottom: 0;">
            <label>Activity Type</label>
            <select name="type" class="form-control">
                <option value="">All Activities</option>
                <option value="login" <?php echo ($filter_type == 'login') ? 'selected' : ''; ?>>Login</option>
                <option value="logout" <?php echo ($filter_type == 'logout') ? 'selected' : ''; ?>>Logout</option>
                <option value="sale" <?php echo ($filter_type == 'sale') ? 'selected' : ''; ?>>Sales</option>
                <option value="product_add" <?php echo ($filter_type == 'product_add') ? 'selected' : ''; ?>>Product Add</option>
                <option value="product_edit" <?php echo ($filter_type == 'product_edit') ? 'selected' : ''; ?>>Product Edit</option>
                <option value="inventory_receive" <?php echo ($filter_type == 'inventory_receive') ? 'selected' : ''; ?>>Inventory</option>
            </select>
        </div>
        
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="activity_report.php" class="btn btn-secondary">Reset</a>
    </form>
</div>

<!-- Activity Log -->
<div class="card">
    <h2 style="margin-bottom: 20px;">📜 Activity Log</h2>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Date/Time</th>
                    <th>User</th>
                    <th>Activity</th>
                    <th>Description</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $displayed_count = 0;
                foreach ($activities as $activity): 
                    // Apply filters
                    if ($filter_type && $activity['activity_type'] != $filter_type) continue;
                    if ($filter_user && $activity['user_id'] != $filter_user) continue;
                    
                    $displayed_count++;
                    
                    // Activity type badges
                    $badge_class = 'primary';
                    $activity_icon = '📝';
                    
                    switch($activity['activity_type']) {
                        case 'login':
                            $badge_class = 'success';
                            $activity_icon = '🔓';
                            break;
                        case 'logout':
                            $badge_class = 'secondary';
                            $activity_icon = '🔒';
                            break;
                        case 'sale':
                            $badge_class = 'primary';
                            $activity_icon = '💰';
                            break;
                        case 'product_add':
                        case 'product_edit':
                            $badge_class = 'warning';
                            $activity_icon = '📦';
                            break;
                        case 'inventory_receive':
                            $badge_class = 'success';
                            $activity_icon = '📥';
                            break;
                    }
                ?>
                <tr>
                    <td><?php echo date('d M Y, h:i:s A', strtotime($activity['created_at'])); ?></td>
                    <td><strong><?php echo $activity['username']; ?></strong></td>
                    <td>
                        <span class="badge badge-<?php echo $badge_class; ?>">
                            <?php echo $activity_icon . ' ' . str_replace('_', ' ', ucfirst($activity['activity_type'])); ?>
                        </span>
                    </td>
                    <td><?php echo $activity['description']; ?></td>
                    <td><?php echo $activity['ip_address']; ?></td>
                </tr>
                <?php endforeach; ?>
                
                <?php if ($displayed_count === 0): ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 40px; color: var(--secondary);">No activities found</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>