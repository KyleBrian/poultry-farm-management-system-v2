<?php
/**
 * File: modules/health/metrics.php
 * Bird health metrics
 * @version 1.0.2
 * @integration_verification PMSFV-029
 */
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/functions.php';

// Check if user is logged in
check_login();

// Page title
$page_title = "Bird Health Metrics";

// Get flocks for dropdown
$flocks = db_query($pdo, "SELECT id, name FROM flocks ORDER BY name");

// Get selected flock ID from query parameters
$selected_flock = isset($_GET['flock_id']) ? intval($_GET['flock_id']) : null;

// Get date range from query parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Build query conditions
$conditions = [];
$params = [];

if ($selected_flock) {
    $conditions[] = "hr.flock_id = ?";
    $params[] = $selected_flock;
}

$conditions[] = "hr.record_date BETWEEN ? AND ?";
$params[] = $start_date;
$params[] = $end_date;

$where_clause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

// Get health metrics summary
$health_summary = db_query_row($pdo, "
    SELECT 
        COUNT(*) as total_records,
        SUM(CASE WHEN record_type = 'routine_check' THEN 1 ELSE 0 END) as routine_checks,
        SUM(CASE WHEN record_type = 'illness' THEN 1 ELSE 0 END) as illness_records,
        SUM(CASE WHEN record_type = 'treatment' THEN 1 ELSE 0 END) as treatment_records,
        SUM(CASE WHEN record_type = 'recovery' THEN 1 ELSE 0 END) as recovery_records,
        SUM(CASE WHEN record_type = 'mortality' THEN 1 ELSE 0 END) as mortality_records,
        SUM(CASE WHEN recovery_status = 'ongoing' THEN 1 ELSE 0 END) as ongoing_cases,
        SUM(CASE WHEN recovery_status = 'recovered' THEN 1 ELSE 0 END) as recovered_cases,
        SUM(CASE WHEN recovery_status = 'deceased' THEN 1 ELSE 0 END) as deceased_cases
    FROM health_records hr
    $where_clause
", $params);

// Get common health issues
$common_issues = db_query($pdo, "
    SELECT diagnosis, COUNT(*) as count
    FROM health_records hr
    $where_clause AND hr.record_type = 'illness' AND hr.diagnosis != ''
    GROUP BY hr.diagnosis
    ORDER BY count DESC
    LIMIT 10
", $params);

// Get health metrics by date for chart
$date_range = [];
$current_date = new DateTime($start_date);
$end_date_obj = new DateTime($end_date);

while ($current_date <= $end_date_obj) {
    $date_range[] = $current_date->format('Y-m-d');
    $current_date->modify('+1 day');
}

// Get illness data for chart
$illness_data = [];
foreach ($date_range as $date) {
    $illness_count = db_query_value($pdo, "
        SELECT COUNT(*) 
        FROM health_records hr
        WHERE hr.record_date = ? AND hr.record_type = 'illness'
        " . ($selected_flock ? " AND hr.flock_id = ?" : ""),
        $selected_flock ? [$date, $selected_flock] : [$date]
    );
    
    $illness_data[] = [
        'date' => $date,
        'count' => (int)$illness_count
    ];
}

// Get mortality data for chart
$mortality_data = [];
foreach ($date_range as $date) {
    $mortality_count = db_query_value($pdo, "
        SELECT COUNT(*) 
        FROM health_records hr
        WHERE hr.record_date = ? AND hr.record_type = 'mortality'
        " . ($selected_flock ? " AND hr.flock_id = ?" : ""),
        $selected_flock ? [$date, $selected_flock] : [$date]
    );
    
    $mortality_data[] = [
        'date' => $date,
        'count' => (int)$mortality_count
    ];
}

// Get recovery data for chart
$recovery_data = [];
foreach ($date_range as $date) {
    $recovery_count = db_query_value($pdo, "
        SELECT COUNT(*) 
        FROM health_records hr
        WHERE hr.record_date = ? AND hr.record_type = 'recovery'
        " . ($selected_flock ? " AND hr.flock_id = ?" : ""),
        $selected_flock ? [$date, $selected_flock] : [$date]
    );
    
    $recovery_data[] = [
        'date' => $date,
        'count' => (int)$recovery_count
    ];
}

// Get flock details if selected
$flock_details = null;
if ($selected_flock) {
    $flock_details = db_query_row($pdo, "SELECT * FROM flocks WHERE id = ?", [$selected_flock]);
}

// Include header
include '../../includes/header.php';
?>

<!-- Health Metrics -->
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Bird Health Metrics</h1>
        <a href="index.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
            <i class="fas fa-arrow-left mr-2"></i> Back to Health Records
        </a>
    </div>
    
    <?php display_flash_message(); ?>
    
    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Filter Metrics</h2>
        <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="flock_id" class="block text-sm font-medium text-gray-700 mb-1">Flock</label>
                <select id="flock_id" name="flock_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <option value="">All Flocks</option>
                    <?php foreach ($flocks as $flock): ?>
                        <option value="<?php echo $flock['id']; ?>" <?php echo ($selected_flock == $flock['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($flock['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
            </div>
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
            </div>
            <div class="md:col-span-3 flex items-center">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded mr-2">
                    <i class="fas fa-filter mr-2"></i> Apply Filters
                </button>
                <a href="metrics.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                    <i class="fas fa-times mr-2"></i> Clear Filters
                </a>
            </div>
        </form>
    </div>
    
    <?php if ($selected_flock && $flock_details): ?>
        <!-- Selected Flock Details -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Flock Details: <?php echo htmlspecialchars($flock_details['name']); ?></h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Breed</h3>
                    <p class="mt-1 text-lg text-gray-900"><?php echo htmlspecialchars($flock_details['breed']); ?></p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Acquisition Date</h3>
                    <p class="mt-1 text-lg text-gray-900"><?php echo format_date($flock_details['acquisition_date']); ?></p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Current Count</h3>
                    <p class="mt-1 text-lg text-gray-900"><?php echo number_format($flock_details['current_count']); ?> birds</p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Status</h3>
                    <p class="mt-1">
                        <?php
                        $status_color = '';
                        switch ($flock_details['status']) {
                            case 'active':
                                $status_color = 'green';
                                break;
                            case 'sold':
                                $status_color = 'blue';
                                break;
                            case 'culled':
                                $status_color = 'yellow';
                                break;
                            case 'deceased':
                                $status_color = 'red';
                                break;
                        }
                        ?>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-<?php echo $status_color; ?>-100 text-<?php echo $status_color; ?>-800">
                            <?php echo ucfirst($flock_details['status']); ?>
                        </span>
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Health Metrics Summary -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-2">Total Records</h2>
            <p class="text-3xl font-bold text-blue-600"><?php echo number_format($health_summary['total_records']); ?></p>
            <div class="mt-2 text-sm text-gray-500">
                <p><?php echo number_format($health_summary['routine_checks']); ?> routine checks</p>
                <p><?php echo number_format($health_summary['illness_records']); ?> illness records</p>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-2">Health Status</h2>
            <p class="text-3xl font-bold text-yellow-600"><?php echo number_format($health_summary['ongoing_cases']); ?></p>
            <div class="mt-2 text-sm text-gray-500">
                <p>Ongoing cases requiring attention</p>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-2">Recovery Rate</h2>
            <?php
            $total_cases = $health_summary['recovered_cases'] + $health_summary['deceased_cases'];
            $recovery_rate = $total_cases > 0 ? ($health_summary['recovered_cases'] / $total_cases) * 100 : 0;
            ?>
            <p class="text-3xl font-bold text-green-600"><?php echo number_format($recovery_rate, 1); ?>%</p>
            <div class="mt-2 text-sm text-gray-500">
                <p><?php echo number_format($health_summary['recovered_cases']); ?> recovered / <?php echo number_format($total_cases); ?> total</p>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-2">Mortality</h2>
            <p class="text-3xl font-bold text-red-600"><?php echo number_format($health_summary['mortality_records']); ?></p>
            <div class="mt-2 text-sm text-gray-500">
                <p>Total mortality incidents</p>
            </div>
        </div>
    </div>
    
    <!-- Health Metrics Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Illness Trend Chart -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Illness Trend</h2>
            <canvas id="illnessChart" height="300"></canvas>
        </div>
        
        <!-- Mortality Trend Chart -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Mortality Trend</h2>
            <canvas id="mortalityChart" height="300"></canvas>
        </div>
    </div>
    
    <!-- Recovery Rate Chart -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Health Metrics Comparison</h2>
        <canvas id="healthMetricsChart" height="300"></canvas>
    </div>
    
    <!-- Common Health Issues -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Common Health Issues</h2>
        
        <?php if (count($common_issues) > 0): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Diagnosis</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Occurrences</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Percentage</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($common_issues as $issue): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($issue['diagnosis']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo number_format($issue['count']); ?> cases</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php 
                                    $percentage = $health_summary['illness_records'] > 0 ? 
                                        ($issue['count'] / $health_summary['illness_records']) * 100 : 0;
                                    ?>
                                    <div class="text-sm text-gray-900"><?php echo number_format($percentage, 1); ?>%</div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-gray-600">No health issues recorded in the selected period.</p>
        <?php endif; ?>
    </div>
</div>

<script>
// Format dates for charts
function formatChartDates(dates) {
    return dates.map(date => {
        const d = new Date(date);
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    });
}

// Illness Trend Chart
var illnessCtx = document.getElementById('illnessChart').getContext('2d');
var illnessChart = new Chart(illnessCtx, {
    type: 'line',
    data: {
        labels: formatChartDates(<?php echo json_encode(array_column($illness_data, 'date')); ?>),
        datasets: [{
            label: 'Illness Cases',
            data: <?php echo json_encode(array_column($illness_data, 'count')); ?>,
            backgroundColor: 'rgba(255, 99, 132, 0.2)',
            borderColor: 'rgba(255, 99, 132, 1)',
            borderWidth: 2,
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Number of Cases'
                }
            }
        }
    }
});

// Mortality Trend Chart
var mortalityCtx = document.getElementById('mortalityChart').getContext('2d');
var mortalityChart = new Chart(mortalityCtx, {
    type: 'line',
    data: {
        labels: formatChartDates(<?php echo json_encode(array_column($mortality_data, 'date')); ?>),
        datasets: [{
            label: 'Mortality Cases',
            data: <?php echo json_encode(array_column($mortality_data, 'count')); ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 2,
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Number of Cases'
                }
            }
        }
    }
});

// Health Metrics Comparison Chart
var healthMetricsCtx = document.getElementById('healthMetricsChart').getContext('2d');
var healthMetricsChart = new Chart(healthMetricsCtx, {
    type: 'bar',
    data: {
        labels: ['Routine Checks', 'Illness Cases', 'Treatments', 'Recoveries', 'Mortalities'],
        datasets: [{
            label: 'Number of Records',
            data: [
                <?php echo $health_summary['routine_checks']; ?>,
                <?php echo $health_summary['illness_records']; ?>,
                <?php echo $health_summary['treatment_records']; ?>,
                <?php echo $health_summary['recovery_records']; ?>,
                <?php echo $health_summary['mortality_records']; ?>
            ],
            backgroundColor: [
                'rgba(75, 192, 192, 0.2)',
                'rgba(255, 99, 132, 0.2)',
                'rgba(255, 206, 86, 0.2)',
                'rgba(54, 162, 235, 0.2)',
                'rgba(153, 102, 255, 0.2)'
            ],
            borderColor: [
                'rgba(75, 192, 192, 1)',
                'rgba(255, 99, 132, 1)',
                'rgba(255, 206, 86, 1)',
                'rgba(54, 162, 235, 1)',
                'rgba(153, 102, 255, 1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Number of Records'
                }
            }
        }
    }
});
</script>

<?php
// Include footer
include '../../includes/footer.php';
?>

