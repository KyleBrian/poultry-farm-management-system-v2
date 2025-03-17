<?php
/**
 * File: modules/health/edit_record.php
 * Edit health record
 * @version 1.0.2
 * @integration_verification PMSFV-030
 */
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/functions.php';

// Check if user is logged in
check_login();

// Check permission
if (!has_permission('manage_health')) {
    set_flash_message('error', 'You do not have permission to edit health records.');
    header("Location: index.php");
    exit();
}

// Check if record ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    set_flash_message('error', 'Invalid health record ID.');
    header("Location: index.php");
    exit();
}

$record_id = intval($_GET['id']);

// Get health record details
$record = db_query_row($pdo, "
    SELECT hr.*, f.name as flock_name, f.breed as flock_breed
    FROM health_records hr
    JOIN flocks f ON hr.flock_id = f.id
    WHERE hr.id = ?
", [$record_id]);

if (!$record) {
    set_flash_message('error', 'Health record not found.');
    header("Location: index.php");
    exit();
}

// Page title
$page_title = "Edit Health Record";

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate input
        $flock_id = intval($_POST['flock_id']);
        $record_date = $_POST['record_date'];
        $record_type = $_POST['record_type'];
        $symptoms = trim($_POST['symptoms'] ?? '');
        $diagnosis = trim($_POST['diagnosis'] ?? '');
        $treatment = trim($_POST['treatment'] ?? '');
        $medication = trim($_POST['medication'] ?? '');
        $recovery_status = $_POST['recovery_status'] ?? null;
        $notes = trim($_POST['notes'] ?? '');
        
        if (empty($flock_id) || empty($record_date) || empty($record_type)) {
            throw new Exception("Please fill in all required fields.");
        }
        
        // Check if flock exists
        $flock = db_query_row($pdo, "SELECT * FROM flocks WHERE id = ?", [$flock_id]);
        
        if (!$flock) {
            throw new Exception("Selected flock does not exist.");
        }
        
        // Update health record
        $data = [
            'flock_id' => $flock_id,
            'record_date' => $record_date,
            'record_type' => $record_type,
            'symptoms' => $symptoms,
            'diagnosis' => $diagnosis,
            'treatment' => $treatment,
            'medication' => $medication,
            'recovery_status' => $recovery_status,
            'notes' => $notes,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $result = db_update($pdo, 'health_records', $data, 'id = ?', [$record_id]);
        
        if ($result !== false) {
            // Log activity
            log_activity($pdo, $_SESSION['user_id'], 'edit_health_record', "Updated health record #$record_id");
            
            // Update flock mortality if record type is mortality
            if ($record_type == 'mortality' && $record['record_type'] != 'mortality') {
                // Increment mortality count
                db_query($pdo, "
                    UPDATE flocks 
                    SET mortality = mortality + 1, 
                        updated_at = NOW() 
                    WHERE id = ?
                ", [$flock_id]);
            } else if ($record_type != 'mortality' && $record['record_type'] == 'mortality') {
                // Decrement mortality count
                db_query($pdo, "
                    UPDATE flocks 
                    SET mortality = GREATEST(0, mortality - 1), 
                        updated_at = NOW() 
                    WHERE id = ?
                ", [$flock_id]);
            }
            
            set_flash_message('success', "Health record updated successfully.");
            header("Location: view_record.php?id=$record_id");
            exit();
        } else {
            throw new Exception("Failed to update health record.");
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get flocks for dropdown
$flocks = db_query($pdo, "SELECT id, name, breed FROM flocks ORDER BY name");

// Include header
include '../../includes/header.php';
?>

<!-- Edit Health Record -->
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Edit Health Record</h1>
        <div>
            <a href="view_record.php?id=<?php echo $record_id; ?>" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-arrow-left mr-2"></i> Back to Record
            </a>
        </div>
    </div>
    
    <?php display_flash_message(); ?>
    
    <?php if (isset($error)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <p><?php echo $error; ?></p>
        </div>
    <?php endif; ?>
    
    <div class="bg-white rounded-lg shadow-md p-6">
        <form method="POST" action="">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Flock -->
                <div>
                    <label for="flock_id" class="block text-sm font-medium text-gray-700 mb-1">Flock *</label>
                    <select id="flock_id" name="flock_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" required>
                        <option value="">Select Flock</option>
                        <?php foreach ($flocks as $flock): ?>
                            <option value="<?php echo $flock['id']; ?>" <?php echo ($record['flock_id'] == $flock['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($flock['name'] . ' (' . $flock['breed'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Record Date -->
                <div>
                    <label for="record_date" class="block text-sm font-medium text-gray-700 mb-1">Record Date *</label>
                    <input type="date" id="record_date" name="record_date" value="<?php echo $record['record_date']; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" required>
                </div>
                
                <!-- Record Type -->
                <div>
                    <label for="record_type" class="block text-sm font-medium text-gray-700 mb-1">Record Type *</label>
                    <select id="record_type" name="record_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" required onchange="toggleFields()">
                        <option value="routine_check" <?php echo ($record['record_type'] == 'routine_check') ? 'selected' : ''; ?>>Routine Check</option>
                        <option value="illness" <?php echo ($record['record_type'] == 'illness') ? 'selected' : ''; ?>>Illness</option>
                        <option value="treatment" <?php echo ($record['record_type'] == 'treatment') ? 'selected' : ''; ?>>Treatment</option>
                        <option value="recovery" <?php echo ($record['record_type'] == 'recovery') ? 'selected' : ''; ?>>Recovery</option>
                        <option value="mortality" <?php echo ($record['record_type'] == 'mortality') ? 'selected' : ''; ?>>Mortality</option>
                    </select>
                </div>
                
                <!-- Recovery Status -->
                <div id="recovery_status_container" style="<?php echo (in_array($record['record_type'], ['illness', 'treatment', 'recovery'])) ? '' : 'display: none;'; ?>">
                    <label for="recovery_status" class="block text-sm font-medium text-gray-700 mb-1">Recovery Status</label>
                    <select id="recovery_status" name="recovery_status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <option value="">Select Status</option>
                        <option value="ongoing" <?php echo ($record['recovery_status'] == 'ongoing') ? 'selected' : ''; ?>>Ongoing</option>
                        <option value="recovered" <?php echo ($record['recovery_status'] == 'recovered') ? 'selected' : ''; ?>>Recovered</option>
                        <option value="deceased" <?php echo ($record['recovery_status'] == 'deceased') ? 'selected' : ''; ?>>Deceased</option>
                    </select>
                </div>
                
                <!-- Symptoms -->
                <div class="md:col-span-2" id="symptoms_container" style="<?php echo (in_array($record['record_type'], ['illness', 'treatment'])) ? '' : 'display: none;'; ?>">
                    <label for="symptoms" class="block text-sm font-medium text-gray-700 mb-1">Symptoms</label>
                    <textarea id="symptoms" name="symptoms" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"><?php echo htmlspecialchars($record['symptoms']); ?></textarea>
                </div>
                
                <!-- Diagnosis -->
                <div class="md:col-span-2" id="diagnosis_container" style="<?php echo (in_array($record['record_type'], ['illness', 'treatment'])) ? '' : 'display: none;'; ?>">
                    <label for="diagnosis" class="block text-sm font-medium text-gray-700 mb-1">Diagnosis</label>
                    <textarea id="diagnosis" name="diagnosis" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"><?php echo htmlspecialchars($record['diagnosis']); ?></textarea>
                </div>
                
                <!-- Treatment -->
                <div class="md:col-span-2" id="treatment_container" style="<?php echo (in_array($record['record_type'], ['treatment', 'recovery'])) ? '' : 'display: none;'; ?>">
                    <label for="treatment" class="block text-sm font-medium text-gray-700 mb-1">Treatment</label>
                    <textarea id="treatment" name="treatment" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"><?php echo htmlspecialchars($record['treatment']); ?></textarea>
                </div>
                
                <!-- Medication -->
                <div class="md:col-span-2" id="medication_container" style="<?php echo (in_array($record['record_type'], ['treatment', 'recovery'])) ? '' :   style="<?php echo (in_array($record['record_type'], ['treatment', 'recovery'])) ? '' : 'display: none;'; ?>">
                    <label for="medication" class="block text-sm font-medium text-gray-700 mb-1">Medication</label>
                    <textarea id="medication" name="medication" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"><?php echo htmlspecialchars($record['medication']); ?></textarea>
                </div>
                
                <!-- Notes -->
                <div class="md:col-span-2">
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea id="notes" name="notes" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"><?php echo htmlspecialchars($record['notes']); ?></textarea>
                </div>
            </div>
            
            <div class="mt-6 flex items-center justify-end">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    <i class="fas fa-save mr-2"></i> Update Record
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleFields() {
    const recordType = document.getElementById('record_type').value;
    
    // Recovery Status
    const recoveryStatusContainer = document.getElementById('recovery_status_container');
    recoveryStatusContainer.style.display = ['illness', 'treatment', 'recovery'].includes(recordType) ? 'block' : 'none';
    
    // Symptoms
    const symptomsContainer = document.getElementById('symptoms_container');
    symptomsContainer.style.display = ['illness', 'treatment'].includes(recordType) ? 'block' : 'none';
    
    // Diagnosis
    const diagnosisContainer = document.getElementById('diagnosis_container');
    diagnosisContainer.style.display = ['illness', 'treatment'].includes(recordType) ? 'block' : 'none';
    
    // Treatment
    const treatmentContainer = document.getElementById('treatment_container');
    treatmentContainer.style.display = ['treatment', 'recovery'].includes(recordType) ? 'block' : 'none';
    
    // Medication
    const medicationContainer = document.getElementById('medication_container');
    medicationContainer.style.display = ['treatment', 'recovery'].includes(recordType) ? 'block' : 'none';
}
</script>

<?php
// Include footer
include '../../includes/footer.php';
?>

```php file="modules/health/metrics.php"
<?php
/**
 * File: modules/health/metrics.php
 * Health metrics and analytics
 * @version 1.0.2
 * @integration_verification PMSFV-032
 */
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/functions.php';

// Check if user is logged in
check_login();

// Page title
$page_title = "Health Metrics";

// Get date range for filtering
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-3 months'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get health metrics
$health_metrics = [
    'total_records' => 0,
    'illness_count' => 0,
    'mortality_count' => 0,
    'recovery_rate' => 0,
    'mortality_rate' => 0
];

// Get health records summary
$health_summary = db_query_row($pdo, "
    SELECT 
        COUNT(*) as total_records,
        SUM(CASE WHEN record_type = 'illness' THEN 1 ELSE 0 END) as illness_count,
        SUM(CASE WHEN record_type = 'mortality' THEN 1 ELSE 0 END) as mortality_count,
        SUM(CASE WHEN recovery_status = 'recovered' THEN 1 ELSE 0 END) as recovered_count,
        SUM(CASE WHEN recovery_status = 'deceased' THEN 1 ELSE 0 END) as deceased_count
    FROM health_records
    WHERE record_date BETWEEN ? AND ?
", [$start_date, $end_date]);

if ($health_summary) {
    $health_metrics['total_records'] = $health_summary['total_records'];
    $health_metrics['illness_count'] = $health_summary['illness_count'];
    $health_metrics['mortality_count'] = $health_summary['mortality_count'];
    
    // Calculate recovery rate
    $total_cases = $health_summary['recovered_count'] + $health_summary['deceased_count'];
    $health_metrics['recovery_rate'] = ($total_cases > 0) ? ($health_summary['recovered_count'] / $total_cases) * 100 : 0;
    
    // Calculate mortality rate
    $total_birds = db_query_row($pdo, "
        SELECT SUM(initial_count) as total_birds
        FROM flocks
        WHERE acquisition_date <= ?
    ", [$end_date]);
    
    $health_metrics['mortality_rate'] = ($total_birds['total_birds'] > 0) ? 
        ($health_summary['mortality_count'] / $total_birds['total_birds']) * 100 : 0;
}

// Get monthly health records
$monthly_records = db_query($pdo, "
    SELECT 
        DATE_FORMAT(record_date, '%Y-%m') as month,
        COUNT(*) as total,
        SUM(CASE WHEN record_type = 'illness' THEN 1 ELSE 0 END) as illness,
        SUM(CASE WHEN record_type = 'mortality' THEN 1 ELSE 0 END) as mortality
    FROM health_records
    WHERE record_date BETWEEN ? AND ?
    GROUP BY DATE_FORMAT(record_date, '%Y-%m')
    ORDER BY month
", [$start_date, $end_date]);

// Get records by flock
$records_by_flock = db_query($pdo, "
    SELECT 
        f.name as flock_name,
        COUNT(hr.id) as total_records,
        SUM(CASE WHEN hr.record_type = 'illness' THEN 1 ELSE 0 END) as illness_count,
        SUM(CASE WHEN hr.record_type = 'mortality' THEN 1 ELSE 0 END) as mortality_count
    FROM health_records hr
    JOIN flocks f ON hr.flock_id = f.id
    WHERE hr.record_date BETWEEN ? AND ?
    GROUP BY hr.flock_id, f.name
    ORDER BY total_records DESC
    LIMIT 10
", [$start_date, $end_date]);

// Get common health issues
$common_issues = db_query($pdo, "
    SELECT 
        diagnosis,
        COUNT(*) as count
    FROM health_records
    WHERE record_date BETWEEN ? AND ? 
        AND diagnosis IS NOT NULL 
        AND diagnosis != ''
    GROUP BY diagnosis
    ORDER BY count DESC
    LIMIT 10
", [$start_date, $end_date]);

// Include header
include '../../includes/header.php';
?>

&lt;!-- Health Metrics -->
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Health Metrics</h1>
        <a href="index.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
            <i class="fas fa-arrow-left mr-2"></i> Back to Health Records
        </a>
    </div>
    
    <?php display_flash_message(); ?>
    
    &lt;!-- Date Range Filter -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Date Range</h2>
        <form method="GET" action="" class="flex flex-wrap items-center gap-4">
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>" class="rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
            </div>
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>" class="rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
            </div>
            <div class="flex items-end">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-filter mr-2"></i> Apply Filter
                </button>
            </div>
        </form>
    </div>
    
    &lt;!-- Health Summary -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-2">Total Records</h2>
            <p class="text-3xl font-bold text-blue-600"><?php echo number_format($health_metrics['total_records']); ?></p>
            <p class="text-sm text-gray-500 mt-1">Health records</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-2">Illness Cases</h2>
            <p class="text-3xl font-bold text-yellow-600"><?php echo number_format($health_metrics['illness_count']); ?></p>
            <p class="text-sm text-gray-500 mt-1">Reported illnesses</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-2">Mortality</h2>
            <p class="text-3xl font-bold text-red-600"><?php echo number_format($health_metrics['mortality_count']); ?></p>
            <p class="text-sm text-gray-500 mt-1">Mortality records</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-2">Recovery Rate</h2>
            <p class="text-3xl font-bold text-green-600"><?php echo number_format($health_metrics['recovery_rate'], 1); ?>%</p>
            <p class="text-sm text-gray-500 mt-1">Of treated cases</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-2">Mortality Rate</h2>
            <p class="text-3xl font-bold text-purple-600"><?php echo number_format($health_metrics['mortality_rate'], 2); ?>%</p>
            <p class="text-sm text-gray-500 mt-1">Of total birds</p>
        </div>
    </div>
    
    &lt;!-- Monthly Health Records Chart -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Monthly Health Records</h2>
        
        <?php if (count($monthly_records) > 0): ?>
            <div class="h-80">
                <canvas id="monthlyHealthChart"></canvas>
            </div>
        <?php else: ?>
            <p class="text-gray-600">No data available for the selected date range.</p>
        <?php endif; ?>
    </div>
    
    &lt;!-- Health Records by Flock -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Health Records by Flock</h2>
            
            <?php if (count($records_by_flock) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Flock</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Total Records</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Illness</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Mortality</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($records_by_flock as $flock): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($flock['flock_name']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <div class="text-sm text-gray-900"><?php echo number_format($flock['total_records']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <div class="text-sm text-yellow-600"><?php echo number_format($flock['illness_count']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <div class="text-sm text-red-600"><?php echo number_format($flock['mortality_count']); ?></div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-gray-600">No data available for the selected date range.</p>
            <?php endif; ?>
        </div>
        
        &lt;!-- Common Health Issues -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Common Health Issues</h2>
            
            <?php if (count($common_issues) > 0): ?>
                <div class="h-80">
                    <canvas id="commonIssuesChart"></canvas>
                </div>
            <?php else: ?>
                <p class="text-gray-600">No diagnosis data available for the selected date range.</p>
            <?php endif; ?>
        </div>
    </div>
    
    &lt;!-- Health Recommendations -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Health Recommendations</h2>
        
        <?php
        // Generate recommendations based on data
        $recommendations = [];
        
        if ($health_metrics['mortality_rate'] > 2) {
            $recommendations[] = [
                'title' => 'High Mortality Rate',
                'description' => 'Your mortality rate is above 2%. Consider reviewing biosecurity measures and consulting with a veterinarian.',
                'icon' => 'exclamation-triangle',
                'color' => 'red'
            ];
        }
        
        if ($health_metrics['recovery_rate'] &lt; 70) {
            $recommendations[] = [
                'title' => 'Low Recovery Rate',
                'description' => 'Your recovery rate is below 70%. Review treatment protocols and consider consulting with a veterinarian.',
                'icon' => 'heartbeat',
                'color' => 'yellow'
            ];
        }
        
        if ($health_metrics['illness_count'] > 10) {
            $recommendations[] = [
                'title' => 'High Illness Rate',
                'description' => 'You have a high number of illness cases. Consider reviewing biosecurity measures, nutrition, and environmental conditions.',
                'icon' => 'virus',
                'color' => 'orange'
            ];
        }
        
        // Add general recommendations if no specific ones
        if (empty($recommendations)) {
            $recommendations[] = [
                'title' => 'Regular Health Checks',
                'description' => 'Continue performing regular health checks on your flocks to maintain good health status.',
                'icon' => 'check-circle',
                'color' => 'green'
            ];
            
            $recommendations[] = [
                'title' => 'Vaccination Schedule',
                'description' => 'Ensure all flocks are following the recommended vaccination schedule.',
                'icon' => 'syringe',
                'color' => 'blue'
            ];
        }
        ?>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php foreach ($recommendations as $recommendation): ?>
                <div class="border border-<?php echo $recommendation['color']; ?>-200 rounded-lg p-4 bg-<?php echo $recommendation['color']; ?>-50">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 mt-1">
                            <i class="fas fa-<?php echo $recommendation['icon']; ?> text-<?php echo $recommendation['color']; ?>-500 text-xl"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-medium text-gray-900"><?php echo $recommendation['title']; ?></h3>
                            <p class="mt-1 text-sm text-gray-600"><?php echo $recommendation['description']; ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

&lt;!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (count($monthly_records) > 0): ?>
    // Monthly Health Records Chart
    const monthlyCtx = document.getElementById('monthlyHealthChart').getContext('2d');
    const monthlyHealthChart = new Chart(monthlyCtx, {
        type: 'bar',
        data: {
            labels: [
                <?php foreach ($monthly_records as $record): ?>
                '<?php echo date("M Y", strtotime($record['month'] . "-01")); ?>',
                <?php endforeach; ?>
            ],
            datasets: [
                {
                    label: 'Total Records',
                    data: [
                        <?php foreach ($monthly_records as $record): ?>
                        <?php echo $record['total']; ?>,
                        <?php endforeach; ?>
                    ],
                    backgroundColor: 'rgba(59, 130, 246, 0.5)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Illness',
                    data: [
                        <?php foreach ($monthly_records as $record): ?>
                        <?php echo $record['illness']; ?>,
                        <?php endforeach; ?>
                    ],
                    backgroundColor: 'rgba(245, 158, 11, 0.5)',
                    borderColor: 'rgba(245, 158, 11, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Mortality',
                    data: [
                        <?php foreach ($monthly_records as $record): ?>
                        <?php echo $record['mortality']; ?>,
                        <?php endforeach; ?>
                    ],
                    backgroundColor: 'rgba(239, 68, 68, 0.5)',
                    borderColor: 'rgba(239, 68, 68, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
    <?php endif; ?>
    
    <?php if (count($common_issues) > 0): ?>
    // Common Health Issues Chart
    const issuesCtx = document.getElementById('commonIssuesChart').getContext('2d');
    const commonIssuesChart = new Chart(issuesCtx, {
        type: 'pie',
        data: {
            labels: [
                <?php foreach ($common_issues as $issue): ?>
                '<?php echo addslashes($issue['diagnosis']); ?>',
                <?php endforeach; ?>
            ],
            datasets: [{
                data: [
                    <?php foreach ($common_issues as $issue): ?>
                    <?php echo $issue['count']; ?>,
                    <?php endforeach; ?>
                ],
                backgroundColor: [
                    'rgba(59, 130, 246, 0.7)',
                    'rgba(16, 185, 129, 0.7)',
                    'rgba(245, 158, 11, 0.7)',
                    'rgba(239, 68, 68, 0.7)',
                    'rgba(139, 92, 246, 0.7)',
                    'rgba(236, 72, 153, 0.7)',
                    'rgba(6, 182, 212, 0.7)',
                    'rgba(249, 115, 22, 0.7)',
                    'rgba(168, 85, 247, 0.7)',
                    'rgba(217, 119, 6, 0.7)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 15
                    }
                }
            }
        }
    });
    <?php endif; ?>
});
</script>

<?php
// Include footer
include '../../includes/footer.php';
?>

