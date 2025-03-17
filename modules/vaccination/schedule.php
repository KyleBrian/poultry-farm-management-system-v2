<?php
require_once '../../config/config.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';

// Get all active flocks
$flocks_query = "SELECT id, name, batch_number, bird_count, hatch_date FROM flocks WHERE status = 'Active' ORDER BY name";
$flocks_result = $conn->query($flocks_query);
$flocks = [];
while ($row = $flocks_result->fetch_assoc()) {
    $flocks[] = $row;
}

// Get all vaccine types
$vaccines_query = "SELECT id, name, description, recommended_age FROM vaccine_types ORDER BY recommended_age";
$vaccines_result = $conn->query($vaccines_query);
$vaccines = [];
while ($row = $vaccines_result->fetch_assoc()) {
    $vaccines[] = $row;
}

// Process form submission for generating schedule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_schedule'])) {
    $flock_id = intval($_POST['flock_id']);
    
    // Get flock details
    $flock_query = "SELECT * FROM flocks WHERE id = ?";
    $stmt = $conn->prepare($flock_query);
    $stmt->bind_param("i", $flock_id);
    $stmt->execute();
    $flock_result = $stmt->get_result();
    $flock = $flock_result->fetch_assoc();
    
    if ($flock) {
        $hatch_date = new DateTime($flock['hatch_date']);
        $success_count = 0;
        $error_count = 0;
        
        // Loop through all vaccines
        foreach ($vaccines as $vaccine) {
            // Calculate vaccination date based on hatch date and recommended age
            $vaccination_date = clone $hatch_date;
            $vaccination_date->add(new DateInterval('P' . $vaccine['recommended_age'] . 'D'));
            
            // Check if this vaccination already exists
            $check_query = "SELECT id FROM vaccinations WHERE flock_id = ? AND vaccine_type_id = ?";
            $stmt = $conn->prepare($check_query);
            $stmt->bind_param("ii", $flock_id, $vaccine['id']);
            $stmt->execute();
            $check_result = $stmt->get_result();
            
            if ($check_result->num_rows == 0) {
                // Insert new vaccination schedule
                $insert_query = "INSERT INTO vaccinations (flock_id, vaccine_type_id, vaccination_date, status, created_by, created_at) 
                                VALUES (?, ?, ?, 'Scheduled', ?, NOW())";
                $stmt = $conn->prepare($insert_query);
                $vaccination_date_str = $vaccination_date->format('Y-m-d');
                $stmt->bind_param("iisi", $flock_id, $vaccine['id'], $vaccination_date_str, $_SESSION['user_id']);
                
                if ($stmt->execute()) {
                    $success_count++;
                } else {
                    $error_count++;
                }
            }
        }
        
        if ($success_count > 0) {
            $_SESSION['success'] = "$success_count vaccination schedules generated successfully";
            
            // Log activity
            $log_query = "INSERT INTO activity_logs (user_id, action, module, description, created_at) VALUES (?, 'create', 'vaccination', ?, NOW())";
            $log_stmt = $conn->prepare($log_query);
            $description = "Generated vaccination schedule for flock: " . $flock['name'];
            $log_stmt->bind_param("is", $_SESSION['user_id'], $description);
            $log_stmt->execute();
        }
        
        if ($error_count > 0) {
            $_SESSION['error'] = "$error_count vaccination schedules failed to generate";
        }
        
        // Redirect to vaccination list
        header("Location: index.php");
        exit();
    } else {
        $_SESSION['error'] = "Flock not found";
    }
}

// Process form submission for adding custom schedule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_custom'])) {
    // Validate input
    $flock_id = intval($_POST['flock_id']);
    $vaccine_ids = $_POST['vaccine_ids'];
    $vaccination_dates = $_POST['vaccination_dates'];
    
    $success_count = 0;
    $error_count = 0;
    
    // Loop through selected vaccines
    for ($i = 0; $i < count($vaccine_ids); $i++) {
        $vaccine_id = intval($vaccine_ids[$i]);
        $vaccination_date = mysqli_real_escape_string($conn, $vaccination_dates[$i]);
        
        if ($vaccine_id > 0 && !empty($vaccination_date)) {
            // Check if this vaccination already exists
            $check_query = "SELECT id FROM vaccinations WHERE flock_id = ? AND vaccine_type_id = ?";
            $stmt = $conn->prepare($check_query);
            $stmt->bind_param("ii", $flock_id, $vaccine_id);
            $stmt->execute();
            $check_result = $stmt->get_result();
            
            if ($check_result->num_rows == 0) {
                // Insert new vaccination schedule
                $insert_query = "INSERT INTO vaccinations (flock_id, vaccine_type_id, vaccination_date, status, created_by, created_at) 
                                VALUES (?, ?, ?, 'Scheduled', ?, NOW())";
                $stmt = $conn->prepare($insert_query);
                $stmt->bind_param("iisi", $flock_id, $vaccine_id, $vaccination_date, $_SESSION['user_id']);
                
                if ($stmt->execute()) {
                    $success_count++;
                } else {
                    $error_count++;
                }
            } else {
                $error_count++;
            }
        }
    }
    
    if ($success_count > 0) {
        $_SESSION['success'] = "$success_count custom vaccination schedules added successfully";
        
        // Log activity
        $log_query = "INSERT INTO activity_logs (user_id, action, module, description, created_at) VALUES (?, 'create', 'vaccination', ?, NOW())";
        $log_stmt = $conn->prepare($log_query);
        $description = "Added custom vaccination schedule";
        $log_stmt->bind_param("is", $_SESSION['user_id'], $description);
        $log_stmt->execute();
    }
    
    if ($error_count > 0) {
        $_SESSION['error'] = "$error_count vaccination schedules failed to add (may already exist)";
    }
    
    // Redirect to vaccination list
    header("Location: index.php");
    exit();
}

// Get upcoming vaccinations
$upcoming_query = "SELECT v.*, f.name as flock_name, f.batch_number, vt.name as vaccine_name 
                  FROM vaccinations v 
                  LEFT JOIN flocks f ON v.flock_id = f.id 
                  LEFT JOIN vaccine_types vt ON v.vaccine_type_id = vt.id 
                  WHERE v.status = 'Scheduled' AND v.vaccination_date >= CURDATE() 
                  ORDER BY v.vaccination_date ASC 
                  LIMIT 10";
$upcoming_result = $conn->query($upcoming_query);
$upcoming_vaccinations = [];
while ($row = $upcoming_result->fetch_assoc()) {
    $upcoming_vaccinations[] = $row;
}
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Vaccination Schedule</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="../../dashboard.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="index.php">Vaccinations</a></li>
                        <li class="breadcrumb-item active">Schedule</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <?php include '../../includes/alerts.php'; ?>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Generate Vaccination Schedule</h3>
                        </div>
                        <form method="post" action="">
                            <div class="card-body">
                                <p class="text-muted">
                                    This will automatically generate a vaccination schedule based on the flock's hatch date and recommended vaccination ages.
                                </p>
                                <div class="form-group">
                                    <label for="flock_id">Select Flock</label>
                                    <select class="form-control" id="flock_id" name="flock_id" required>
                                        <option value="">Select Flock</option>
                                        <?php foreach ($flocks as $flock): ?>
                                            <option value="<?php echo $flock['id']; ?>" data-hatch="<?php echo $flock['hatch_date']; ?>">
                                                <?php echo htmlspecialchars($flock['name'] . ' (' . $flock['batch_number'] . ') - Hatched: ' . date('Y-m-d', strtotime($flock['hatch_date']))); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>Vaccines to be Scheduled</label>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Vaccine</th>
                                                    <th>Recommended Age (days)</th>
                                                    <th>Estimated Date</th>
                                                </tr>
                                            </thead>
                                            <tbody id="vaccine-schedule">
                                                <tr>
                                                    <td colspan="3" class="text-center">Select a flock to view the vaccination schedule</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" name="generate_schedule" class="btn btn-primary">Generate Schedule</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title">Add Custom Vaccination Schedule</h3>
                        </div>
                        <form method="post" action="" id="custom-form">
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="custom_flock_id">Select Flock</label>
                                    <select class="form-control" id="custom_flock_id" name="flock_id" required>
                                        <option value="">Select Flock</option>
                                        <?php foreach ($flocks as $flock): ?>
                                            <option value="<?php echo $flock['id']; ?>">
                                                <?php echo htmlspecialchars($flock['name'] . ' (' . $flock['batch_number'] . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div id="custom-schedules">
                                    <div class="row custom-schedule-row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Vaccine</label>
                                                <select class="form-control" name="vaccine_ids[]" required>
                                                    <option value="">Select Vaccine</option>
                                                    <?php foreach ($vaccines as $vaccine): ?>
                                                        <option value="<?php echo $vaccine['id']; ?>">
                                                            <?php echo htmlspecialchars($vaccine['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Vaccination Date</label>
                                                <input type="date" class="form-control" name="vaccination_dates[]" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="button" id="add-more-btn" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-plus"></i> Add More Vaccines
                                </button>
                            </div>
                            <div class="card-footer">
                                <button type="submit" name="add_custom" class="btn btn-info">Add Custom Schedule</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Upcoming Vaccinations</h3>
                            <div class="card-tools">
                                <a href="index.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-list"></i> View All Vaccinations
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Flock</th>
                                            <th>Vaccine</th>
                                            <th>Date</th>
                                            <th>Days Until Due</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($upcoming_vaccinations) > 0): ?>
                                            <?php foreach ($upcoming_vaccinations as $vaccination): 
                                                $days_until = floor((strtotime($vaccination['vaccination_date']) - time()) / (60 * 60 * 24));
                                            ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($vaccination['flock_name'] . ' (' . $vaccination['batch_number'] . ')'); ?></td>
                                                    <td><?php echo htmlspecialchars($vaccination['vaccine_name']); ?></td>
                                                    <td><?php echo date('Y-m-d', strtotime($vaccination['vaccination_date'])); ?></td>
                                                    <td>
                                                        <span class="badge <?php echo ($days_until <= 3) ? 'badge-danger' : (($days_until <= 7) ? 'badge-warning' : 'badge-info'); ?>">
                                                            <?php echo $days_until; ?> days
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a href="view.php?id=<?php echo $vaccination['id']; ?>" class="btn btn-sm btn-info">
                                                            <i class="fas fa-eye"></i> View
                                                        </a>
                                                        <a href="record.php?id=<?php echo $vaccination['id']; ?>" class="btn btn-sm btn-success">
                                                            <i class="fas fa-check"></i> Mark Complete
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center">No upcoming vaccinations scheduled</td>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Generate schedule based on flock selection
    const flockSelect = document.getElementById('flock_id');
    const vaccineSchedule = document.getElementById('vaccine-schedule');
    const vaccines = <?php echo json_encode($vaccines); ?>;
    
    flockSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const hatchDate = selectedOption.getAttribute('data-hatch');
        
        if (hatchDate) {
            let scheduleHtml = '';
            
            vaccines.forEach(function(vaccine) {
                const hatchDateObj = new Date(hatchDate);
                const vaccinationDate = new Date(hatchDateObj);
                vaccinationDate.setDate(hatchDateObj.getDate() + parseInt(vaccine.recommended_age));
                
                const formattedDate = vaccinationDate.toISOString().split('T')[0];
                
                scheduleHtml += `
                    <tr>
                        <td>${vaccine.name}</td>
                        <td>${vaccine.recommended_age}</td>
                        <td>${formattedDate}</td>
                    </tr>
                `;
            });
            
            vaccineSchedule.innerHTML = scheduleHtml;
        } else {
            vaccineSchedule.innerHTML = '<tr><td colspan="3" class="text-center">Select a flock to view the vaccination schedule</td></tr>';
        }
    });
    
    // Add more custom schedules
    const addMoreBtn = document.getElementById('add-more-btn');
    const customSchedules = document.getElementById('custom-schedules');
    
    addMoreBtn.addEventListener('click', function() {
        const row = document.querySelector('.custom-schedule-row').cloneNode(true);
        const selects = row.querySelectorAll('select');
        const inputs = row.querySelectorAll('input');
        
        // Reset values
        selects.forEach(select => select.selectedIndex = 0);
        inputs.forEach(input => input.value = '');
        
        customSchedules.appendChild(row);
    });
});
</script>

<?php
require_once '../../includes/footer.php';
?>

