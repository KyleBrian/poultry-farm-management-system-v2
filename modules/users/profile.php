<?php
require_once '../../config/config.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';

// Determine which user profile to show
$user_id = isset($_GET['id']) ? intval($_GET['id']) : $_SESSION['user_id'];

// Check permissions - only admins can view other profiles
if ($user_id != $_SESSION['user_id'] && $_SESSION['user_role'] !== 'admin') {
    $_SESSION['error'] = "You don't have permission to view this profile";
    header("Location: ../../dashboard.php");
    exit();
}

// Get user data
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error'] = "User not found";
    header("Location: index.php");
    exit();
}

$user = $result->fetch_assoc();

// Process profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    
    // Check if email is already used by another user
    $check_query = "SELECT id FROM users WHERE email = ? AND id != ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();
    $check_result = $stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $_SESSION['error'] = "Email is already in use by another user";
    } else {
        // Update user profile
        $update_query = "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sssssi", $first_name, $last_name, $email, $phone, $address, $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Profile updated successfully";
            
            // Update session data if updating own profile
            if ($user_id == $_SESSION['user_id']) {
                $_SESSION['user_name'] = $first_name . ' ' . $last_name;
                $_SESSION['user_email'] = $email;
            }
            
            // Refresh user data
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
        } else {
            $_SESSION['error'] = "Failed to update profile: " . $conn->error;
        }
    }
}

// Process password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate input
    if (strlen($new_password) < 8) {
        $_SESSION['error'] = "New password must be at least 8 characters long";
    } elseif ($new_password !== $confirm_password) {
        $_SESSION['error'] = "New passwords do not match";
    } else {
        // Verify current password
        if (password_verify($current_password, $user['password'])) {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Password changed successfully";
            } else {
                $_SESSION['error'] = "Failed to change password: " . $conn->error;
            }
        } else {
            $_SESSION['error'] = "Current password is incorrect";
        }
    }
}

// Get user activity logs
$logs_query = "SELECT * FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 10";
$stmt = $conn->prepare($logs_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$logs_result = $stmt->get_result();
$activity_logs = [];
while ($log = $logs_result->fetch_assoc()) {
    $activity_logs[] = $log;
}
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">User Profile</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="../../dashboard.php">Home</a></li>
                        <?php if ($_SESSION['user_role'] === 'admin' && $user_id != $_SESSION['user_id']): ?>
                            <li class="breadcrumb-item"><a href="index.php">Users</a></li>
                        <?php endif; ?>
                        <li class="breadcrumb-item active">Profile</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <?php include '../../includes/alerts.php'; ?>
            
            <div class="row">
                <div class="col-md-3">
                    <!-- Profile Image -->
                    <div class="card card-primary card-outline">
                        <div class="card-body box-profile">
                            <div class="text-center">
                                <img class="profile-user-img img-fluid img-circle" src="../../assets/img/user-avatar.png" alt="User profile picture">
                            </div>

                            <h3 class="profile-username text-center"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>

                            <p class="text-muted text-center"><?php echo ucfirst($user['role']); ?></p>

                            <ul class="list-group list-group-unbordered mb-3">
                                <li class="list-group-item">
                                    <b>Username</b> <a class="float-right"><?php echo htmlspecialchars($user['username']); ?></a>
                                </li>
                                <li class="list-group-item">
                                    <b>Email</b> <a class="float-right"><?php echo htmlspecialchars($user['email']); ?></a>
                                </li>
                                <li class="list-group-item">
                                    <b>Status</b> 
                                    <span class="float-right badge <?php echo ($user['status']) ? 'badge-success' : 'badge-secondary'; ?>">
                                        <?php echo ($user['status']) ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </li>
                                <li class="list-group-item">
                                    <b>Member Since</b> <a class="float-right"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></a>
                                </li>
                            </ul>

                            <?php if ($user_id == $_SESSION['user_id'] || $_SESSION['user_role'] === 'admin'): ?>
                                <a href="edit.php?id=<?php echo $user_id; ?>" class="btn btn-primary btn-block"><b>Edit Profile</b></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-9">
                    <div class="card">
                        <div class="card-header p-2">
                            <ul class="nav nav-pills">
                                <li class="nav-item"><a class="nav-link active" href="#profile" data-toggle="tab">Profile</a></li>
                                <?php if ($user_id == $_SESSION['user_id']): ?>
                                    <li class="nav-item"><a class="nav-link" href="#password" data-toggle="tab">Change Password</a></li>
                                <?php endif; ?>
                                <li class="nav-item"><a class="nav-link" href="#activity" data-toggle="tab">Activity</a></li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content">
                                <!-- Profile Tab -->
                                <div class="active tab-pane" id="profile">
                                    <form class="form-horizontal" method="post" action="">
                                        <div class="form-group row">
                                            <label for="first_name" class="col-sm-2 col-form-label">First Name</label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="last_name" class="col-sm-2 col-form-label">Last Name</label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="email" class="col-sm-2 col-form-label">Email</label>
                                            <div class="col-sm-10">
                                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="phone" class="col-sm-2 col-form-label">Phone</label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="address" class="col-sm-2 col-form-label">Address</label>
                                            <div class="col-sm-10">
                                                <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <div class="offset-sm-2 col-sm-10">
                                                <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                
                                <!-- Change Password Tab -->
                                <?php if ($user_id == $_SESSION['user_id']): ?>
                                <div class="tab-pane" id="password">
                                    <form class="form-horizontal" method="post" action="">
                                        <div class="form-group row">
                                            <label for="current_password" class="col-sm-3 col-form-label">Current Password</label>
                                            <div class="col-sm-9">
                                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="new_password" class="col-sm-3 col-form-label">New Password</label>
                                            <div class="col-sm-9">
                                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                                <small class="form-text text-muted">Password must be at least 8 characters long</small>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="confirm_password" class="col-sm-3 col-form-label">Confirm New Password</label>
                                            <div class="col-sm-9">
                                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <div class="offset-sm-3 col-sm-9">
                                                <button type="submit" name="change_password" class="btn btn-danger">Change Password</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Activity Tab -->
                                <div class="tab-pane" id="activity">
                                    <?php if (count($activity_logs) > 0): ?>
                                        <div class="timeline">
                                            <?php 
                                            $current_date = '';
                                            foreach ($activity_logs as $log): 
                                                $log_date = date('Y-m-d', strtotime($log['created_at']));
                                                if ($log_date != $current_date) {
                                                    $current_date = $log_date;
                                                    echo '<div class="time-label">';
                                                    echo '<span class="bg-primary">' . date('d M Y', strtotime($log['created_at'])) . '</span>';
                                                    echo '</div>';
                                                }
                                            ?>
                                            <div>
                                                <i class="fas fa-<?php 
                                                    switch ($log['action']) {
                                                        case 'login':
                                                            echo 'sign-in-alt bg-success';
                                                            break;
                                                        case 'logout':
                                                            echo 'sign-out-alt bg-warning';
                                                            break;
                                                        case 'create':
                                                            echo 'plus bg-info';
                                                            break;
                                                        case 'update':
                                                            echo 'edit bg-primary';
                                                            break;
                                                        case 'delete':
                                                            echo 'trash bg-danger';
                                                            break;
                                                        default:
                                                            echo 'dot-circle bg-secondary';
                                                    }
                                                ?>"></i>
                                                <div class="timeline-item">
                                                    <span class="time"><i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($log['created_at'])); ?></span>
                                                    <h3 class="timeline-header no-border">
                                                        <?php 
                                                        echo ucfirst($log['action']) . ' - ' . $log['module'];
                                                        if (!empty($log['description'])) {
                                                            echo ': ' . htmlspecialchars($log['description']);
                                                        }
                                                        ?>
                                                    </h3>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                            <div>
                                                <i class="fas fa-clock bg-gray"></i>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-center">No activity logs found</p>
                                    <?php endif; ?>
                                </div>
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

