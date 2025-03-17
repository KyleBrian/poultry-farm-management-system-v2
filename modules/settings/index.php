<?php
require_once '../../config/config.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';

// Check for admin privileges
if ($_SESSION['user_role'] !== 'admin') {
    $_SESSION['error'] = "You don't have permission to access settings";
    header("Location: ../../dashboard.php");
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Determine which form was submitted
    if (isset($_POST['update_general'])) {
        // Update general settings
        $farm_name = mysqli_real_escape_string($conn, $_POST['farm_name']);
        $farm_address = mysqli_real_escape_string($conn, $_POST['farm_address']);
        $farm_contact = mysqli_real_escape_string($conn, $_POST['farm_contact']);
        $farm_email = mysqli_real_escape_string($conn, $_POST['farm_email']);
        $currency_symbol = mysqli_real_escape_string($conn, $_POST['currency_symbol']);
        $tax_rate = floatval($_POST['tax_rate']);
        
        // Update settings in database
        $query = "UPDATE settings SET 
                  value = CASE 
                    WHEN name = 'farm_name' THEN ?
                    WHEN name = 'farm_address' THEN ?
                    WHEN name = 'farm_contact' THEN ?
                    WHEN name = 'farm_email' THEN ?
                    WHEN name = 'currency_symbol' THEN ?
                    WHEN name = 'tax_rate' THEN ?
                    ELSE value
                  END
                  WHERE name IN ('farm_name', 'farm_address', 'farm_contact', 'farm_email', 'currency_symbol', 'tax_rate')";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssss", $farm_name, $farm_address, $farm_contact, $farm_email, $currency_symbol, $tax_rate);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "General settings updated successfully";
        } else {
            $_SESSION['error'] = "Failed to update general settings: " . $conn->error;
        }
        
    } elseif (isset($_POST['update_email'])) {
        // Update email settings
        $smtp_host = mysqli_real_escape_string($conn, $_POST['smtp_host']);
        $smtp_port = intval($_POST['smtp_port']);
        $smtp_username = mysqli_real_escape_string($conn, $_POST['smtp_username']);
        $smtp_password = mysqli_real_escape_string($conn, $_POST['smtp_password']);
        $smtp_encryption = mysqli_real_escape_string($conn, $_POST['smtp_encryption']);
        
        // Update settings in database
        $query = "UPDATE settings SET 
                  value = CASE 
                    WHEN name = 'smtp_host' THEN ?
                    WHEN name = 'smtp_port' THEN ?
                    WHEN name = 'smtp_username' THEN ?
                    WHEN name = 'smtp_password' THEN ?
                    WHEN name = 'smtp_encryption' THEN ?
                    ELSE value
                  END
                  WHERE name IN ('smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_encryption')";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssss", $smtp_host, $smtp_port, $smtp_username, $smtp_password, $smtp_encryption);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Email settings updated successfully";
        } else {
            $_SESSION['error'] = "Failed to update email settings: " . $conn->error;
        }
        
    } elseif (isset($_POST['update_notification'])) {
        // Update notification settings
        $enable_email_alerts = isset($_POST['enable_email_alerts']) ? 1 : 0;
        $enable_sms_alerts = isset($_POST['enable_sms_alerts']) ? 1 : 0;
        $low_stock_threshold = intval($_POST['low_stock_threshold']);
        $mortality_alert_threshold = floatval($_POST['mortality_alert_threshold']);
        
        // Update settings in database
        $query = "UPDATE settings SET 
                  value = CASE 
                    WHEN name = 'enable_email_alerts' THEN ?
                    WHEN name = 'enable_sms_alerts' THEN ?
                    WHEN name = 'low_stock_threshold' THEN ?
                    WHEN name = 'mortality_alert_threshold' THEN ?
                    ELSE value
                  END
                  WHERE name IN ('enable_email_alerts', 'enable_sms_alerts', 'low_stock_threshold', 'mortality_alert_threshold')";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssss", $enable_email_alerts, $enable_sms_alerts, $low_stock_threshold, $mortality_alert_threshold);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Notification settings updated successfully";
        } else {
            $_SESSION['error'] = "Failed to update notification settings: " . $conn->error;
        }
        
    } elseif (isset($_POST['update_backup'])) {
        // Update backup settings
        $auto_backup = isset($_POST['auto_backup']) ? 1 : 0;
        $backup_frequency = mysqli_real_escape_string($conn, $_POST['backup_frequency']);
        $backup_retention = intval($_POST['backup_retention']);
        
        // Update settings in database
        $query = "UPDATE settings SET 
                  value = CASE 
                    WHEN name = 'auto_backup' THEN ?
                    WHEN name = 'backup_frequency' THEN ?
                    WHEN name = 'backup_retention' THEN ?
                    ELSE value
                  END
                  WHERE name IN ('auto_backup', 'backup_frequency', 'backup_retention')";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sss", $auto_backup, $backup_frequency, $backup_retention);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Backup settings updated successfully";
        } else {
            $_SESSION['error'] = "Failed to update backup settings: " . $conn->error;
        }
        
    } elseif (isset($_POST['update_system'])) {
        // Update system settings
        $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
        $session_timeout = intval($_POST['session_timeout']);
        $date_format = mysqli_real_escape_string($conn, $_POST['date_format']);
        $timezone = mysqli_real_escape_string($conn, $_POST['timezone']);
        
        // Update settings in database
        $query = "UPDATE settings SET 
                  value = CASE 
                    WHEN name = 'maintenance_mode' THEN ?
                    WHEN name = 'session_timeout' THEN ?
                    WHEN name = 'date_format' THEN ?
                    WHEN name = 'timezone' THEN ?
                    ELSE value
                  END
                  WHERE name IN ('maintenance_mode', 'session_timeout', 'date_format', 'timezone')";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssss", $maintenance_mode, $session_timeout, $date_format, $timezone);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "System settings updated successfully";
        } else {
            $_SESSION['error'] = "Failed to update system settings: " . $conn->error;
        }
    }
    
    // Redirect to refresh the page and avoid form resubmission
    header("Location: index.php");
    exit();
}

// Fetch all settings from database
$settings = [];
$query = "SELECT * FROM settings";
$result = $conn->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $settings[$row['name']] = $row['value'];
    }
}

// Set default values if not found in database
$defaults = [
    'farm_name' => 'Poultry Farm',
    'farm_address' => '',
    'farm_contact' => '',
    'farm_email' => '',
    'currency_symbol' => '$',
    'tax_rate' => '0',
    'smtp_host' => '',
    'smtp_port' => '587',
    'smtp_username' => '',
    'smtp_password' => '',
    'smtp_encryption' => 'tls',
    'enable_email_alerts' => '0',
    'enable_sms_alerts' => '0',
    'low_stock_threshold' => '10',
    'mortality_alert_threshold' => '5',
    'auto_backup' => '0',
    'backup_frequency' => 'daily',
    'backup_retention' => '7',
    'maintenance_mode' => '0',
    'session_timeout' => '30',
    'date_format' => 'Y-m-d',
    'timezone' => 'UTC'
];

// Merge defaults with database settings
foreach ($defaults as $key => $value) {
    if (!isset($settings[$key])) {
        $settings[$key] = $value;
    }
}
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">System Settings</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="../../dashboard.php">Home</a></li>
                        <li class="breadcrumb-item active">Settings</li>
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
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Settings Menu</h3>
                        </div>
                        <div class="card-body p-0">
                            <ul class="nav nav-pills flex-column">
                                <li class="nav-item">
                                    <a href="#general" class="nav-link active" data-toggle="tab">
                                        <i class="fas fa-cog"></i> General Settings
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#email" class="nav-link" data-toggle="tab">
                                        <i class="fas fa-envelope"></i> Email Configuration
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#notification" class="nav-link" data-toggle="tab">
                                        <i class="fas fa-bell"></i> Notification Settings
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#backup" class="nav-link" data-toggle="tab">
                                        <i class="fas fa-database"></i> Backup & Restore
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#system" class="nav-link" data-toggle="tab">
                                        <i class="fas fa-server"></i> System Settings
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Actions</h3>
                        </div>
                        <div class="card-body">
                            <a href="backup.php" class="btn btn-primary btn-block">
                                <i class="fas fa-download"></i> Create Backup
                            </a>
                            <button type="button" class="btn btn-warning btn-block" data-toggle="modal" data-target="#restoreModal">
                                <i class="fas fa-upload"></i> Restore Backup
                            </button>
                            <button type="button" class="btn btn-danger btn-block" data-toggle="modal" data-target="#resetModal">
                                <i class="fas fa-trash"></i> Reset Settings
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-9">
                    <div class="tab-content">
                        <!-- General Settings Tab -->
                        <div class="tab-pane active" id="general">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">General Settings</h3>
                                </div>
                                <div class="card-body">
                                    <form method="post" action="">
                                        <div class="form-group">
                                            <label for="farm_name">Farm Name</label>
                                            <input type="text" class="form-control" id="farm_name" name="farm_name" value="<?php echo htmlspecialchars($settings['farm_name']); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="farm_address">Farm Address</label>
                                            <textarea class="form-control" id="farm_address" name="farm_address" rows="3"><?php echo htmlspecialchars($settings['farm_address']); ?></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="farm_contact">Contact Number</label>
                                            <input type="text" class="form-control" id="farm_contact" name="farm_contact" value="<?php echo htmlspecialchars($settings['farm_contact']); ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="farm_email">Email Address</label>
                                            <input type="email" class="form-control" id="farm_email" name="farm_email" value="<?php echo htmlspecialchars($settings['farm_email']); ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="currency_symbol">Currency Symbol</label>
                                            <input type="text" class="form-control" id="currency_symbol" name="currency_symbol" value="<?php echo htmlspecialchars($settings['currency_symbol']); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="tax_rate">Default Tax Rate (%)</label>
                                            <input type="number" step="0.01" min="0" max="100" class="form-control" id="tax_rate" name="tax_rate" value="<?php echo htmlspecialchars($settings['tax_rate']); ?>">
                                        </div>
                                        <button type="submit" name="update_general" class="btn btn-primary">Save Changes</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Email Configuration Tab -->
                        <div class="tab-pane" id="email">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Email Configuration</h3>
                                </div>
                                <div class="card-body">
                                    <form method="post" action="">
                                        <div class="form-group">
                                            <label for="smtp_host">SMTP Host</label>
                                            <input type="text" class="form-control" id="smtp_host" name="smtp_host" value="<?php echo htmlspecialchars($settings['smtp_host']); ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="smtp_port">SMTP Port</label>
                                            <input type="number" class="form-control" id="smtp_port" name="smtp_port" value="<?php echo htmlspecialchars($settings['smtp_port']); ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="smtp_username">SMTP Username</label>
                                            <input type="text" class="form-control" id="smtp_username" name="smtp_username" value="<?php echo htmlspecialchars($settings['smtp_username']); ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="smtp_password">SMTP Password</label>
                                            <input type="password" class="form-control" id="smtp_password" name="smtp_password" value="<?php echo htmlspecialchars($settings['smtp_password']); ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="smtp_encryption">Encryption</label>
                                            <select class="form-control" id="smtp_encryption" name="smtp_encryption">
                                                <option value="tls" <?php echo ($settings['smtp_encryption'] == 'tls') ? 'selected' : ''; ?>>TLS</option>
                                                <option value="ssl" <?php echo ($settings['smtp_encryption'] == 'ssl') ? 'selected' : ''; ?>>SSL</option>
                                                <option value="none" <?php echo ($settings['smtp_encryption'] == 'none') ? 'selected' : ''; ?>>None</option>
                                            </select>
                                        </div>
                                        <button type="submit" name="update_email" class="btn btn-primary">Save Changes</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Notification Settings Tab -->
                        <div class="tab-pane" id="notification">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Notification Settings</h3>
                                </div>
                                <div class="card-body">
                                    <form method="post" action="">
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="enable_email_alerts" name="enable_email_alerts" <?php echo ($settings['enable_email_alerts'] == '1') ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="enable_email_alerts">Enable Email Alerts</label>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="enable_sms_alerts" name="enable_sms_alerts" <?php echo ($settings['enable_sms_alerts'] == '1') ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="enable_sms_alerts">Enable SMS Alerts</label>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="low_stock_threshold">Low Stock Alert Threshold</label>
                                            <input type="number" min="0" class="form-control" id="low_stock_threshold" name="low_stock_threshold" value="<?php echo htmlspecialchars($settings['low_stock_threshold']); ?>">
                                            <small class="form-text text-muted">Receive alerts when inventory items fall below this quantity</small>
                                        </div>
                                        <div class="form-group">
                                            <label for="mortality_alert_threshold">Mortality Alert Threshold (%)</label>
                                            <input type="number" step="0.01" min="0" max="100" class="form-control" id="mortality_alert_threshold" name="mortality_alert_threshold" value="<?php echo htmlspecialchars($settings['mortality_alert_threshold']); ?>">
                                            <small class="form-text text-muted">Receive alerts when daily mortality exceeds this percentage</small>
                                        </div>
                                        <button type="submit" name="update_notification" class="btn btn-primary">Save Changes</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Backup & Restore Tab -->
                        <div class="tab-pane" id="backup">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Backup & Restore Settings</h3>
                                </div>
                                <div class="card-body">
                                    <form method="post" action="">
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="auto_backup" name="auto_backup" <?php echo ($settings['auto_backup'] == '1') ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="auto_backup">Enable Automatic Backups</label>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="backup_frequency">Backup Frequency</label>
                                            <select class="form-control" id="backup_frequency" name="backup_frequency">
                                                <option value="daily" <?php echo ($settings['backup_frequency'] == 'daily') ? 'selected' : ''; ?>>Daily</option>
                                                <option value="weekly" <?php echo ($settings['backup_frequency'] == 'weekly') ? 'selected' : ''; ?>>Weekly</option>
                                                <option value="monthly" <?php echo ($settings['backup_frequency'] == 'monthly') ? 'selected' : ''; ?>>Monthly</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="backup_retention">Backup Retention (days)</label>
                                            <input type="number" min="1" class="form-control" id="backup_retention" name="backup_retention" value="<?php echo htmlspecialchars($settings['backup_retention']); ?>">
                                            <small class="form-text text-muted">Number of days to keep backups before automatic deletion</small>
                                        </div>
                                        <button type="submit" name="update_backup" class="btn btn-primary">Save Changes</button>
                                    </form>
                                    
                                    <hr>
                                    
                                    <h5>Backup History</h5>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Filename</th>
                                                    <th>Date Created</th>
                                                    <th>Size</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                // List backup files
                                                $backup_dir = '../../backups/';
                                                if (is_dir($backup_dir)) {
                                                    $files = scandir($backup_dir);
                                                    $backup_files = [];
                                                    
                                                    foreach ($files as $file) {
                                                        if ($file != '.' && $file != '..' && pathinfo($file, PATHINFO_EXTENSION) == 'sql') {
                                                            $backup_files[] = [
                                                                'name' => $file,
                                                                'date' => date('Y-m-d H:i:s', filemtime($backup_dir . $file)),
                                                                'size' => filesize($backup_dir . $file)
                                                            ];
                                                        }
                                                    }
                                                    
                                                    // Sort by date (newest first)
                                                    usort($backup_files, function($a, $b) {
                                                        return strtotime($b['date']) - strtotime($a['date']);
                                                    });
                                                    
                                                    if (count($backup_files) > 0) {
                                                        foreach ($backup_files as $file) {
                                                            echo '<tr>';
                                                            echo '<td>' . htmlspecialchars($file['name']) . '</td>';
                                                            echo '<td>' . htmlspecialchars($file['date']) . '</td>';
                                                            echo '<td>' . round($file['size'] / 1024, 2) . ' KB</td>';
                                                            echo '<td>';
                                                            echo '<a href="download_backup.php?file=' . urlencode($file['name']) . '" class="btn btn-sm btn-info"><i class="fas fa-download"></i></a> ';
                                                            echo '<a href="restore_backup.php?file=' . urlencode($file['name']) . '" class="btn btn-sm btn-warning" onclick="return confirm(\'Are you sure you want to restore this backup? Current data will be overwritten.\');"><i class="fas fa-upload"></i></a> ';
                                                            echo '<a href="delete_backup.php?file=' . urlencode($file['name']) . '" class="btn btn-sm btn-danger" onclick="return confirm(\'Are you sure you want to delete this backup?\');"><i class="fas fa-trash"></i></a>';
                                                            echo '</td>';
                                                            echo '</tr>';
                                                        }
                                                    } else {
                                                        echo '<tr><td colspan="4" class="text-center">No backup files found</td></tr>';
                                                    }
                                                } else {
                                                    echo '<tr><td colspan="4" class="text-center">Backup directory not found</td></tr>';
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- System Settings Tab -->
                        <div class="tab-pane" id="system">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">System Settings</h3>
                                </div>
                                <div class="card-body">
                                    <form method="post" action="">
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="maintenance_mode" name="maintenance_mode" <?php echo ($settings['maintenance_mode'] == '1') ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="maintenance_mode">Maintenance Mode</label>
                                            </div>
                                            <small class="form-text text-muted">When enabled, only administrators can access the system</small>
                                        </div>
                                        <div class="form-group">
                                            <label for="session_timeout">Session Timeout (minutes)</label>
                                            <input type="number" min="1" class="form-control" id="session_timeout" name="session_timeout" value="<?php echo htmlspecialchars($settings['session_timeout']); ?>">
                                            <small class="form-text text-muted">Time of inactivity before user is automatically logged out</small>
                                        </div>
                                        <div class="form-group">
                                            <label for="date_format">Date Format</label>
                                            <select class="form-control" id="date_format" name="date_format">
                                                <option value="Y-m-d" <?php echo ($settings['date_format'] == 'Y-m-d') ? 'selected' : ''; ?>>YYYY-MM-DD</option>
                                                <option value="m/d/Y" <?php echo ($settings['date_format'] == 'm/d/Y') ? 'selected' : ''; ?>>MM/DD/YYYY</option>
                                                <option value="d/m/Y" <?php echo ($settings['date_format'] == 'd/m/Y') ? 'selected' : ''; ?>>DD/MM/YYYY</option>
                                                <option value="d.m.Y" <?php echo ($settings['date_format'] == 'd.m.Y') ? 'selected' : ''; ?>>DD.MM.YYYY</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="timezone">Timezone</label>
                                            <select class="form-control" id="timezone" name="timezone">
                                                <?php
                                                $timezones = DateTimeZone::listIdentifiers();
                                                foreach ($timezones as $tz) {
                                                    $selected = ($settings['timezone'] == $tz) ? 'selected' : '';
                                                    echo "<option value=\"$tz\" $selected>$tz</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <button type="submit" name="update_system" class="btn btn-primary">Save Changes</button>
                                    </form>
                                    
                                    <hr>
                                    
                                    <h5>System Information</h5>
                                    <table class="table table-bordered">
                                        <tr>
                                            <th>PHP Version</th>
                                            <td><?php echo phpversion(); ?></td>
                                        </tr>
                                        <tr>
                                            <th>MySQL Version</th>
                                            <td><?php echo $conn->server_info; ?></td>
                                        </tr>
                                        <tr>
                                            <th>Server Software</th>
                                            <td><?php echo $_SERVER['SERVER_SOFTWARE']; ?></td>
                                        </tr>
                                        <tr>
                                            <th>Application Version</th>
                                            <td>1.0.0</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Restore Backup Modal -->
<div class="modal fade" id="restoreModal" tabindex="-1" role="dialog" aria-labelledby="restoreModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="restoreModalLabel">Restore Backup</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="restore_upload.php" method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="backup_file">Select Backup File</label>
                        <input type="file" class="form-control-file" id="backup_file" name="backup_file" accept=".sql" required>
                        <small class="form-text text-muted">Only .sql files are supported</small>
                    </div>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> Warning: Restoring a backup will overwrite all current data. This action cannot be undone.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Restore Backup</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reset Settings Modal -->
<div class="modal fade" id="resetModal" tabindex="-1" role="dialog" aria-labelledby="resetModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resetModalLabel">Reset Settings</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="reset_settings.php" method="post">
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> Warning: This will reset all settings to their default values. This action cannot be undone.
                    </div>
                    <div class="form-group">
                        <label for="confirm_reset">Type "RESET" to confirm</label>
                        <input type="text" class="form-control" id="confirm_reset" name="confirm_reset" required pattern="RESET">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reset Settings</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once '../../includes/footer.php';
?>

