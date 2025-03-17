<?php
/**
 * File: modules/health/index.php
 * Health records management
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
$page_title = "Health Records";

// Handle record deletion
if (isset($_GET['delete']) && has_permission('manage_health')) {
    $record_id = intval($_GET['delete']);
    
    try {
        // Check if record exists
        $record = db_query_row($pdo, "SELECT * FROM health_records WHERE id = ?", [$record_id]);
        
        if (!$record) {
            throw new Exception("Health record not found.");
        }
        
        // Delete record
        db_delete($pdo, 'health_records', 'id = ?', [$record_id]);
        
        // Log activity
        log_activity($pdo, $_SESSION['user_id'], 'delete_health_record', "Deleted health record #$record_id");
        
        set_flash_message('success', "Health record deleted successfully.");
    } catch (Exception $e) {
        set_flash_message('error', $e->getMessage());
    }
    
    header("Location: index.php");
    exit();
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = ITEMS_PER_PAGE;
$offset = ($page - 1) * $items_per_page;

// Filters
$flock_id = isset($_GET['flock_id']) ? intval($_GET['flock_id']) : null;
$record_type = isset($_GET['record_type']) ? $_GET['record_type'] : null;
$recovery_status = isset($_GET['recovery_status']) ? $_GET['recovery_status'] : null;
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;
$search = isset($_GET['search']) ? $_GET['search'] : null;

// Build query conditions
$conditions = [];
$params = [];

if ($flock_id) {
    $conditions[] = "hr.flock_id = ?";
    $params[] = $flock_id;
}

if ($record_type) {
    $conditions[] = "hr.record_type = ?";
    $params[] = $record_type;
}

if ($recovery_status) {
    $conditions[] = "hr.recovery_status = ?";
    $params[] = $recovery_status;
}

if ($start_date) {
    $conditions[] = "hr.record_date >= ?";
    $params[] = $start_date;
}

if ($end_date) {
    $conditions[] = "hr.record_date <= ?";
    $params[] = $end_date;
}

if ($search) {
    $conditions[] = "(hr.symptoms LIKE ? OR hr.diagnosis LIKE ? OR hr.treatment LIKE ? OR hr.notes LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

// Get total records
$total_query = "
    SELECT COUNT(*) 
    FROM health_records hr
    $where_clause
";
$total_stmt = $pdo->prepare($total_query);
$total_stmt->execute($params);
$total_records = $total_stmt->fetchColumn();

// Calculate total pages
$total_pages = ceil($total_records / $items_per_page);

// Get health records
$query = "
    SELECT hr.*, f.name as flock_name, f.breed as flock_breed, u.username as recorded_by_name
    FROM health_records hr
    JOIN flocks f ON hr.flock_id = f.id
    JOIN users u ON hr.recorded_by = u.id
    $where_clause
    ORDER BY hr.record_date DESC, hr.id DESC
    LIMIT $items_per_page OFFSET $offset
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$health_records = $stmt->fetchAll();

// Get flocks for filter dropdown
$flocks = db_query($pdo, "SELECT id, name, breed FROM flocks ORDER BY name");

// Include header
include '../../includes/header.php';
?>

<!-- Health Records Management -->
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Health Records</h1>
        <?php if (has_permission('manage_health')): ?>
            <a href="add_record.php" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-plus mr-2"></i> Add Health Record
            </a>
        <?php endif; ?>
    </div>
    
    <?php display_flash_message(); ?>
    
    <!-- Health Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <?php
        // Get health statistics
        $health_stats = db_query_row($pdo, "
            SELECT 
                COUNT(*) as total_records,
                SUM(CASE WHEN record_type = 'illness' THEN 1 ELSE 0 END) as illness_count,
                SUM(CASE WHEN record_type = 'mortality' THEN 1 ELSE 0 END) as mortality_count,
                SUM(CASE WHEN recovery_status = 'ongoing' THEN 1 ELSE 0 END) as ongoing_count
            FROM health_records
        ");
        ?>
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-2">Total Records</h2>
            <p class="text-3xl font-bold text-blue-600"><?php echo number_format($health_stats['total_records']); ?></p>
            <p class="text-sm text-gray-500 mt-1">Health records</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-2">Illness Cases</h2>
            <p class="text-3xl font-bold text-yellow-600"><?php echo number_format($health_stats['illness_count']); ?></p>
            <p class="text-sm text-gray-500 mt-1">Reported illnesses</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-2">Mortality</h2>
            <p class="text-3xl font-bold text-red-600"><?php echo number_format($health_stats['mortality_count']); ?></p>
            <p class="text-sm text-gray-500 mt-1">Mortality records</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-2">Ongoing Cases</h2>
            <p class="text-3xl font-bold text-purple-600"><?php echo number_format($health_stats['ongoing_count']); ?></p>
            <p class="text-sm text-gray-500 mt-1">Cases requiring attention</p>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Filter Health Records</h2>
        <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="flock_id" class="block text-sm font-medium text-gray-700 mb-1">Flock</label>
                <select id="flock_id" name="flock_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <option value="">All Flocks</option>
                    <?php foreach ($flocks as $flock): ?>
                        <option value="<?php echo $flock['id']; ?>" <?php echo ($flock_id == $flock['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($flock['name'] . ' (' . $flock['breed'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="record_type" class="block text-sm font-medium text-gray-700 mb-1">Record Type</label>
                <select id="record_type" name="record_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <option value="">All Types</option>
                    <option value="routine_check" <?php echo ($record_type == 'routine_check') ? 'selected' : ''; ?>>Routine Check</option>
                    <option value="illness" <?php echo ($record_type == 'illness') ? 'selected' : ''; ?>>Illness</option>
                    <option value="treatment" <?php echo ($record_type == 'treatment') ? 'selected' : ''; ?>>Treatment</option>
                    <option value="recovery" <?php echo ($record_type == 'recovery') ? 'selected' : ''; ?>>Recovery</option>
                    <option value="mortality" <?php echo ($record_type == 'mortality') ? 'selected' : ''; ?>>Mortality</option>
                </select>
            </div>
            <div>
                <label for="recovery_status" class="block text-sm font-medium text-gray-700 mb-1">Recovery Status</label>
                <select id="recovery_status" name="recovery_status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <option value="">All Statuses</option>
                    <option value="ongoing" <?php echo ($recovery_status == 'ongoing') ? 'selected' : ''; ?>>Ongoing</option>
                    <option value="recovered" <?php echo ($recovery_status == 'recovered') ? 'selected' : ''; ?>>Recovered</option>
                    <option value="deceased" <?php echo ($recovery_status == 'deceased') ? 'selected' : ''; ?>>Deceased</option>
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
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search ?? ''); ?>" placeholder="Search symptoms, diagnosis, etc." class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
            </div>
            <div class="md:col-span-3 flex items-center justify-end">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded mr-2">
                    <i class="fas fa-filter mr-2"></i> Apply Filters
                </button>
                <a href="index.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                    <i class="fas fa-times mr-2"></i> Clear
                </a>
            </div>
        </form>
    </div>
    
    <!-- Health Records Table -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Health Records</h2>
        
        <?php if (count($health_records) > 0): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Flock</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Record Type</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recorded By</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($health_records as $record): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo format_date($record['record_date']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($record['flock_name']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($record['flock_breed']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $type_color = '';
                                    switch ($record['record_type']) {
                                        case 'routine_check':
                                            $type_color = 'blue';
                                            break;
                                        case 'illness':
                                            $type_color = 'red';
                                            break;
                                        case 'treatment':
                                            $type_color = 'yellow';
                                            break;
                                        case 'recovery':
                                            $type_color = 'green';
                                            break;
                                        case 'mortality':
                                            $type_color = 'red';
                                            break;
                                    }
                                    ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-<?php echo $type_color; ?>-100 text-<?php echo $type_color; ?>-800">
                                        <?php echo ucfirst(str_replace('_', ' ', $record['record_type'])); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $status_color = '';
                                    switch ($record['recovery_status']) {
                                        case 'ongoing':
                                            $status_color = 'yellow';
                                            break;
                                        case 'recovered':
                                            $status_color = 'green';
                                            break;
                                        case 'deceased':
                                            $status_color = 'red';
                                            break;
                                        default:
                                            $status_color = 'gray';
                                    }
                                    ?>
                                    <?php if (!empty($record['recovery_status'])): ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-<?php echo $status_color; ?>-100 text-<?php echo $status_color; ?>-800">
                                            <?php echo ucfirst($record['recovery_status']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-500">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($record['recorded_by_name']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <a href="view_record.php?id=<?php echo $record['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <?php if (has_permission('manage_health')): ?>
                                        <a href="edit_record.php?id=<?php echo $record['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="index.php?delete=<?php echo $record['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this health record?');">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="mt-6">
                    <?php
                    $url_pattern = 'index.php?page=:page';
                    if ($flock_id) $url_pattern .= '&flock_id=' . $flock_id;
                    if ($record_type) $url_pattern .= '&record_type=' . $record_type;
                    if ($recovery_status) $url_pattern .= '&recovery_status=' . $recovery_status;
                    if ($start_date) $url_pattern .= '&start_date=' . $start_date;
                    if ($end_date) $url_pattern .= '&end_date=' . $end_date;
                    if ($search) $url_pattern .= '&search=' . urlencode($search);
                    
                    echo get_pagination($total_records, $items_per_page, $page, $url_pattern);
                    ?>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            No health records found. Please adjust your filters or add a new health record.
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Quick Links -->
    <div class="mt-6 flex justify-end">
        <a href="metrics.php" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded">
            <i class="fas fa-chart-bar mr-2"></i> Health Metrics
        </a>
    </div>
</div>

<?php
// Include footer
include '../../includes/footer.php';
?>

