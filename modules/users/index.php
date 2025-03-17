<?php
require_once '../../config/config.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';

// Check for admin privileges for certain actions
$is_admin = ($_SESSION['user_role'] === 'admin');

// Process user status toggle
if (isset($_POST['toggle_status']) && $is_admin) {
    $user_id = intval($_POST['user_id']);
    $current_status = intval($_POST['current_status']);
    $new_status = $current_status ? 0 : 1;
    
    $update_query = "UPDATE users SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ii", $new_status, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "User status updated successfully";
    } else {
        $_SESSION['error'] = "Failed to update user status: " . $conn->error;
    }
    
    // Redirect to refresh the page
    header("Location: index.php");
    exit();
}

// Process user deletion
if (isset($_POST['delete_user']) && $is_admin) {
    $user_id = intval($_POST['user_id']);
    
    // Prevent deleting own account
    if ($user_id == $_SESSION['user_id']) {
        $_SESSION['error'] = "You cannot delete your own account";
    } else {
        $delete_query = "DELETE FROM users WHERE id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "User deleted successfully";
        } else {
            $_SESSION['error'] = "Failed to delete user: " . $conn->error;
        }
    }
    
    // Redirect to refresh the page
    header("Location: index.php");
    exit();
}

// Get search parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build query
$query = "SELECT * FROM users WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ssss";
}

if (!empty($role_filter)) {
    $query .= " AND role = ?";
    $params[] = $role_filter;
    $types .= "s";
}

if ($status_filter !== '') {
    $query .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "i";
}

$query .= " ORDER BY created_at DESC";

// Prepare and execute query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">User Management</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="../../dashboard.php">Home</a></li>
                        <li class="breadcrumb-item active">Users</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <?php include '../../includes/alerts.php'; ?>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">User List</h3>
                            <div class="card-tools">
                                <?php if ($is_admin): ?>
                                <a href="add.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-user-plus"></i> Add New User
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <form method="get" action="">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <input type="text" class="form-control" name="search" placeholder="Search by name, username or email" value="<?php echo htmlspecialchars($search); ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <select class="form-control" name="role">
                                                        <option value="">All Roles</option>
                                                        <option value="admin" <?php echo ($role_filter == 'admin') ? 'selected' : ''; ?>>Administrator</option>
                                                        <option value="manager" <?php echo ($role_filter == 'manager') ? 'selected' : ''; ?>>Farm Manager</option>
                                                        <option value="staff" <?php echo ($role_filter == 'staff') ? 'selected' : ''; ?>>Staff</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <select class="form-control" name="status">
                                                        <option value="">All Status</option>
                                                        <option value="1" <?php echo ($status_filter == '1') ? 'selected' : ''; ?>>Active</option>
                                                        <option value="0" <?php echo ($status_filter == '0') ? 'selected' : ''; ?>>Inactive</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <button type="submit" class="btn btn-primary btn-block">
                                                    <i class="fas fa-search"></i> Search
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($users) > 0): ?>
                                            <?php foreach ($users as $user): ?>
                                                <tr>
                                                    <td><?php echo $user['id']; ?></td>
                                                    <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                    <td>
                                                        <span class="badge <?php 
                                                            echo ($user['role'] == 'admin') ? 'badge-danger' : 
                                                                (($user['role'] == 'manager') ? 'badge-warning' : 'badge-info'); 
                                                        ?>">
                                                            <?php echo ucfirst($user['role']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?php echo ($user['status']) ? 'badge-success' : 'badge-secondary'; ?>">
                                                            <?php echo ($user['status']) ? 'Active' : 'Inactive'; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <a href="profile.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            
                                                            <?php if ($is_admin): ?>
                                                                <a href="edit.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                                
                                                                <form method="post" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to toggle this user\'s status?');">
                                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                    <input type="hidden" name="current_status" value="<?php echo $user['status']; ?>">
                                                                    <button type="submit" name="toggle_status" class="btn btn-sm <?php echo ($user['status']) ? 'btn-warning' : 'btn-success'; ?>">
                                                                        <i class="fas <?php echo ($user['status']) ? 'fa-ban' : 'fa-check'; ?>"></i>
                                                                    </button>
                                                                </form>
                                                                
                                                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                                    <form method="post" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                        <button type="submit" name="delete_user" class="btn btn-sm btn-danger">
                                                                            <i class="fas fa-trash"></i>
                                                                        </button>
                                                                    </form>
                                                                <?php endif; ?>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center">No users found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php
require_once '../../includes/footer.php';
?>

