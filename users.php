<?php
$page_title = 'Users';
include 'header.php';
requireAdmin();

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        $result = createUser(
            sanitizeInput($_POST['username']),
            sanitizeInput($_POST['password']),
            sanitizeInput($_POST['full_name']),
            sanitizeInput($_POST['role'])
        );
        
        if ($result) {
            $message = 'User added successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error adding user. Username might already exist.';
            $message_type = 'danger';
        }
    } elseif (isset($_POST['update_user'])) {
        $result = updateUser(
            intval($_POST['user_id']),
            sanitizeInput($_POST['full_name']),
            sanitizeInput($_POST['role']),
            sanitizeInput($_POST['status'])
        );
        
        if ($result) {
            $message = 'User updated successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error updating user.';
            $message_type = 'danger';
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $result = deleteUser(intval($_GET['delete']));
    if ($result) {
        $message = 'User deleted successfully!';
        $message_type = 'success';
    } else {
        $message = 'Cannot delete this user.';
        $message_type = 'danger';
    }
}

$users = getAllUsers();
?>

<h1>👥 User Management</h1>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
<?php endif; ?>

<div class="card" style="margin-bottom: 30px;">
    <h2 style="margin-bottom: 20px;">Add New User</h2>
    
    <form method="POST">
        <div class="form-row">
            <div class="form-group">
                <label>Username *</label>
                <input type="text" name="username" class="form-control" placeholder="Enter username" required>
                <small>Use a unique username</small>
            </div>
            
            <div class="form-group">
                <label>Password *</label>
                <input type="password" name="password" class="form-control" required minlength="6">
                <small>Minimum 6 characters</small>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="full_name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Role *</label>
                <select name="role" class="form-control" required>
                    <option value="cashier">Cashier</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
        </div>
        
        <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
    </form>
</div>

<div class="card">
    <h2 style="margin-bottom: 20px;">All Users</h2>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Full Name</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><strong><?php echo $user['username']; ?></strong></td>
                    <td><?php echo $user['full_name']; ?></td>
                    <td>
                        <span class="badge badge-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                            <?php echo ucfirst($user['role']); ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-<?php echo $user['status'] === 'active' ? 'success' : 'danger'; ?>">
                            <?php echo ucfirst($user['status']); ?>
                        </span>
                    </td>
                    <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                    <td>
                        <?php if ($user['id'] != 1): ?>
                            <button onclick="toggleUserStatus(<?php echo $user['id']; ?>, '<?php echo $user['status'] === 'active' ? 'inactive' : 'active'; ?>')" class="btn btn-secondary btn-sm">
                                <?php echo $user['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                            </button>
                            <a href="users.php?delete=<?php echo $user['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this user?')">Delete</a>
                        <?php else: ?>
                            <span class="badge badge-warning">Default Admin</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleUserStatus(userId, newStatus) {
    const formData = new FormData();
    formData.append('update_user', '1');
    formData.append('user_id', userId);
    formData.append('status', newStatus);
    
    // Get current values (you might want to make these hidden in the table)
    formData.append('full_name', document.querySelector(`tr[data-user-id="${userId}"] .user-name`).textContent);
    formData.append('role', document.querySelector(`tr[data-user-id="${userId}"] .user-role`).textContent.toLowerCase());
    
    // Submit form
    fetch('users.php', {
        method: 'POST',
        body: formData
    }).then(() => location.reload());
}
</script>

<?php include 'footer.php'; ?>