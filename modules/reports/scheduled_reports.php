<?php
/**
 * File: modules/reports/scheduled_reports.php
 * Scheduled reports management
 * @version 1.0.1
 * @integration_verification PMSFV-027
 */
$page_title = "Scheduled Reports";
require_once '../../includes/header.php';
require_once '../../includes/functions.php';

// Check if user has appropriate permissions
if (!in_array($_SESSION['user_role'], ['admin', 'manager'])) {
    $_SESSION['error_msg'] = "You don't have permission to access this page.";
    header("Location: " . BASE_URL . "dashboard.php");
    exit();
}

// Handle form submission for adding/editing scheduled report
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validate inputs
        $template_id = $_POST['template_id'];
        $schedule_name = $_POST['schedule_name'];
        $frequency = $_POST['frequency'];
        $day_of_week = $_POST['day_of_week'] ?? null;
        $day_of_month = $_POST['day_of_month'] ?? null;
        $time = $_POST['time'];
        $email_recipients = $_POST['email_recipients'];
        $active = isset($_POST['active']) ? 1 : 0;
        
        if (empty($template_id)) {
            throw new Exception("Please select a report template.");
        }
        
        if (empty($schedule_name)) {
            throw new Exception("Schedule name is required.");
        }
        
        if ($frequency == 'weekly' && empty($day_of_week)) {
            throw new Exception("Please select a day of the week for weekly reports.");
        }
        
        if ($frequency == 'monthly' && empty($day_of_month)) {
            throw new Exception("Please select a day of the month for monthly reports.");
        }
        
        if (empty($time)) {
            throw new Exception("Please specify a time for the report to run.");
        }
        
        if (empty($email_recipients)) {
            throw new Exception("Please specify at least one email recipient.");
        }
        
        // Check if editing existing schedule
        if (isset($_POST['schedule_id'])) {
            $schedule_id = $_POST['schedule_id'];
            
            $stmt = $pdo->prepare("
                UPDATE scheduled_reports SET
                    template_id = ?,
                    schedule_name = ?,
                    frequency = ?,
                    day_of_week = ?,
                    day_of_month = ?,
                    time = ?,
                    email_recipients = ?,
                    active = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([
                $template_id,
                $schedule_name,
                $frequency,
                $day_of_week,
                $day_of_month,
                $time,
                $email_recipients,
                $active,
                $schedule_id
            ]);
            
            $_SESSION['success_msg'] = "Scheduled report updated successfully.";
        } else {
            // Add new scheduled report
            $stmt = $pdo->prepare("
                INSERT INTO scheduled_reports (
                    template_id, schedule_name, frequency,
                    day_of_week, day_of_month, time,
                    email_recipients, active, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $template_id,
                $schedule_name,
                $frequency,
                $day_of_week,
                $day_of_month,
                $time,
                $email_recipients,
                $active,
                $_SESSION['user_id']
            ]);
            
            $_SESSION['success_msg'] = "Scheduled report created successfully.";
        }
        
        header("Location: scheduled_reports.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error_msg'] = $e->getMessage();
    }
}

// Handle deletion of scheduled report
if (isset($_GET['delete'])) {
    $schedule_id = intval($_GET['delete']);
    
    $stmt = $pdo->prepare("DELETE FROM scheduled_reports WHERE id = ?");
    $stmt->execute([$schedule_id]);
    
    $_SESSION['success_msg'] = "Scheduled report deleted successfully.";
    header("Location: scheduled_reports.php");
    exit();
}

// Handle toggling active status
if (isset($_GET['toggle'])) {
    $schedule_id = intval($_GET['toggle']);
    
    $stmt = $pdo->prepare("UPDATE scheduled_reports SET active = NOT active WHERE id = ?");
    $stmt->execute([$schedule_id]);
    
    $_SESSION['success_msg'] = "Scheduled report status updated successfully.";
    header("Location: scheduled_reports.php");
    exit();
}

// Get scheduled reports
$stmt = $pdo->query("
    SELECT sr.*, rt.template_name, u.username as created_by_name
    FROM scheduled_reports sr
    JOIN report_templates rt ON sr.template_id = rt.id
    JOIN users u ON sr.created_by = u.id
    ORDER BY sr.created_at DESC
");
$scheduled_reports = $stmt->fetchAll();

// Get report templates for dropdown
$stmt = $pdo->query("SELECT id, template_name, report_type FROM report_templates ORDER BY template_name");
$report_templates = $stmt->fetchAll();
?>

<div class="mb-4 flex justify-between items-center">
    <h1 class="text-2xl font-bold">Scheduled Reports</h1>
    <button class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded" onclick="showAddScheduleForm()">
        <i class="fas fa-plus mr-2"></i>Add Schedule
    </button>
</div>

<?php
if (isset($_SESSION['success_msg'])) {
    echo "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4' role='alert'>{$_SESSION['success_msg']}</div>";
    unset($_SESSION['success_msg']);
}
if (isset($_SESSION['error_msg'])) {
    echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>{$_SESSION['error_msg']}</div>";
    unset($_SESSION['error_msg']);
}
?>

<!-- Scheduled Reports Table -->
<div class="bg-white shadow-md rounded my-6">
    <table class="min-w-max w-full table-auto">
        <thead>
            <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                <th class="py-3 px-6 text-left">Schedule Name</th>
                <th class="py-3 px-6 text-left">Report Template</th>
                <th class="py-3 px-6 text-center">Frequency</th>
                <th class="py-3 px-6 text-center">Next Run</th>
                <th class="py-3 px-6 text-center">Recipients</th>
                <th class="py-3 px-6 text-center">Status</th>
                <th class="py-3 px-6 text-center">Actions</th>
            </tr>
        </thead>
        <tbody class="text-gray-600 text-sm font-light">
            <?php if (count($scheduled_reports) > 0): ?>
                <?php foreach ($scheduled_reports as $report): ?>
                    <tr class="border-b border-gray-200 hover:bg-gray-100">
                        <td class="py-3 px-6 text-left whitespace-nowrap">
                            <span class="font-medium"><?php echo htmlspecialchars($report['schedule_name']); ?></span>
                        </td>
                        <td class="py-3 px-6 text-left">
                            <span><?php echo htmlspecialchars($report['template_name']); ?></span>
                        </td>
                        <td class="py-3 px-6 text-center">
                            <?php
                            $frequency_text = ucfirst($report['frequency']);
                            if ($report['frequency'] == 'weekly' && !empty($report['day_of_week'])) {
                                $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                                $frequency_text .= ' (' . $days[$report['day_of_week']] . ')';
                            } elseif ($report['frequency'] == 'monthly' && !empty($report['day_of_month'])) {
                                $frequency_text .= ' (Day ' . $report['day_of_month'] . ')';
                            }
                            echo $frequency_text;
                            ?>
                        </td>
                        <td class="py-3 px-6 text-center">
                            <?php
                            // Calculate next run date
                            $next_run = '';
                            $time = $report['time'];
                            
                            if ($report['frequency'] == 'daily') {
                                $next_run = date('Y-m-d ' . $time);
                                if (strtotime($next_run) < time()) {
                                    $next_run = date('Y-m-d ' . $time, strtotime('+1 day'));
                                }
                            } elseif ($report['frequency'] == 'weekly') {
                                $day_of_week = $report['day_of_week'];
                                $current_day = date('w');
                                $days_until = ($day_of_week - $current_day + 7) % 7;
                                if ($days_until == 0 && strtotime(date('Y-m-d ' . $time)) < time()) {
                                    $days_until = 7;
                                }
                                $next_run = date('Y-m-d ' . $time, strtotime("+$days_until days"));
                            } elseif ($report['frequency'] == 'monthly') {
                                $day_of_month = $report['day_of_month'];
                                $current_day = date('j');
                                $current_month = date('n');
                                $current_year = date('Y');
                                
                                if ($current_day <= $day_of_month && strtotime(date('Y-m-' . $day_of_month . ' ' . $time)) > time()) {
                                    $next_run = date('Y-m-' . $day_of_month . ' ' . $time);
                                } else {
                                    $next_month = $current_month == 12 ? 1 : $current_month + 1;
                                    $next_year = $current_month == 12 ? $current_year + 1 : $current_year;
                                    $next_run = date('Y-m-d ' . $time, strtotime("$next_year-$next_month-$day_of_month"));
                                }
                            }
                            
                            echo $next_run ? date('M j, Y g:i A', strtotime($next_run)) : 'N/A';
                            ?>
                        </td>
                        <td class="py-3 px-6 text-center">
                            <?php
                            $recipients = explode(',', $report['email_recipients']);
                            echo count($recipients);
                            ?>
                        </td>
                        <td class="py-3 px-6 text-center">
                            <?php if ($report['active']): ?>
                                <span class="bg-green-200 text-green-600 py-1 px-3 rounded-full text-xs">Active</span>
                            <?php else: ?>
                                <span class="bg-red-200 text-red-600 py-1 px-3 rounded-full text-xs">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-6 text-center">
                            <div class="flex item-center justify-center">
                                <a href="scheduled_reports.php?toggle=<?php echo $report['id']; ?>" class="w-4 mr-2 transform hover:text-blue-500 hover:scale-110" title="<?php echo $report['active'] ? 'Deactivate' : 'Activate'; ?>">
                                    <i class="fas fa-<?php echo $report['active'] ? 'toggle-on' : 'toggle-off'; ?>"></i>
                                </a>
                                <a href="#" class="w-4 mr-2 transform hover:text-purple-500 hover:scale-110" onclick="showEditScheduleForm(<?php echo htmlspecialchars(json_encode($report)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="scheduled_reports.php?delete=<?php echo $report['id']; ?>" class="w-4 mr-2 transform hover:text-red-500 hover:scale-110" onclick="return confirm('Are you sure you want to delete this scheduled report?');">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr class="border-b border-gray-200">
                    <td class="py-3 px-6 text-center" colspan="7">No scheduled reports found</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add/Edit Schedule Form Modal -->
<div id="scheduleFormModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-2xl">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold" id="modalTitle">Add Scheduled Report</h2>
            <button class="text-gray-500 hover:text-gray-700" onclick="hideScheduleForm()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" action="">
            <input type="hidden" id="schedule_id" name="schedule_id">
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="schedule_name">
                    Schedule Name *
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="schedule_name" name="schedule_name" type="text" required>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="template_id">
                    Report Template *
                </label>
                <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="template_id" name="template_id" required>
                    <option value="">Select Report Template</option>
                    <?php foreach ($report_templates as $template): ?>
                        <option value="<?php echo $template['id']; ?>">
                            <?php echo htmlspecialchars($template['template_name'] . ' (' . ucfirst($template['report_type']) . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="frequency">
                    Frequency *
                </label>
                <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="frequency" name="frequency" required onchange="toggleFrequencyOptions()">
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                </select>
            </div>
            
            <div id="weekly_options" class="mb-4 hidden">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="day_of_week">
                    Day of Week *
                </label>
                <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="day_of_week" name="day_of_week">
                    <option value="0">Sunday</option>
                    <option value="1">Monday</option>
                    <option value="2">Tuesday</option>
                    <option value="3">Wednesday</option>
                    <option value="4">Thursday</option>
                    <option value="5">Friday</option>
                    <option value="6">Saturday</option>
                </select>
            </div>
            
            <div id="monthly_options" class="mb-4 hidden">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="day_of_month">
                    Day of Month *
                </label>
                <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="day_of_month" name="day_of_month">
                    <?php for ($i = 1; $i <= 31; $i++): ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="time">
                    Time *
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="time" name="time" type="time" required>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="email_recipients">
                    Email Recipients * (comma separated)
                </label>
                <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="email_recipients" name="email_recipients" rows="2" required></textarea>
            </div>
            
            <div class="mb-6">
                <label class="inline-flex items-center">
                    <input type="checkbox" class="form-checkbox" name="active" id="active" value="1" checked>
                    <span class="ml-2">Active</span>
                </label>
            </div>
            
            <div class="flex items-center justify-between">
                <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">
                    Save Schedule
                </button>
                <button class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="button" onclick="hideScheduleForm()">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showAddScheduleForm() {
    document.getElementById('modalTitle').textContent = 'Add Scheduled Report';
    document.getElementById('schedule_id').value = '';
    document.getElementById('schedule_name').value = '';
    document.getElementById('template_id').value = '';
    document.getElementById('frequency').value = 'daily';
    document.getElementById('time').value = '08:00';
    document.getElementById('email_recipients').value = '';
    document.getElementById('active').checked = true;
    
    toggleFrequencyOptions();
    
    document.getElementById('scheduleFormModal').classList.remove('hidden');
}

function showEditScheduleForm(report) {
    document.getElementById('modalTitle').textContent = 'Edit Scheduled Report';
    document.getElementById('schedule_id').value = report.id;
    document.getElementById('schedule_name').value = report.schedule_name;
    document.getElementById('template_id').value = report.template_id;
    document.getElementById('frequency').value = report.frequency;
    document.getElementById('time').value = report.time;
    document.getElementById('email_recipients').value = report.email_recipients;
    document.getElementById('active').checked = report.active == 1;
    
    if (report.frequency == 'weekly' && report.day_of_week !== null) {
        document.getElementById('day_of_week').value = report.day_of_week;
    }
    
    if (report.frequency == 'monthly' && report.day_of_month !== null) {
        document.getElementById('day_of_month').value = report.day_of_month;
    }
    
    toggleFrequencyOptions();
    
    document.getElementById('scheduleFormModal').classList.remove('hidden');
}

function hideScheduleForm() {
    document.getElementById('scheduleFormModal').classList.add('hidden');
}

function toggleFrequencyOptions() {
    const frequency = document.getElementById('frequency').value;
    const weeklyOptions = document.getElementById('weekly_options');
    const monthlyOptions = document.getElementById('monthly_options');
    
    weeklyOptions.classList.add('hidden');
    monthlyOptions.classList.add('hidden');
    
    if (frequency == 'weekly') {
        weeklyOptions.classList.remove('hidden');
        document.getElementById('day_of_week').required = true;
        document.getElementById('day_of_month').required = false;
    } else if (frequency == 'monthly') {
        monthlyOptions.classList.remove('hidden');
        document.getElementById('day_of_week').required = false;
        document.getElementById('day_of_month').required = true;
    } else {
        document.getElementById('day_of_week').required = false;
        document.getElementById('day_of_month').required = false;
    }
}
</script>

<?php require_once '../../includes/footer.php'; ?>

