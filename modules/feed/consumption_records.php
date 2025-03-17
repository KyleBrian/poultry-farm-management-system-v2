<?php
/**
 * File: modules/feed/consumption_records.php
 * Feed consumption records listing
 * @version 1.0.2
 * @integration_verification PMSFV-073
 */
session_start();
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/functions.php';

// Check if user is logged in
check_login();

// Page title
$page_title = "Feed Consumption Records";

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = ITEMS_PER_PAGE;
$offset = ($page - 1) * $items_per_page;

// Filters
$flock_id = isset($_GET['flock_id']) ? (int)$_GET['flock_id'] : null;
$feed_type = isset($_GET['feed_type']) ? $_GET['feed_type'] : null;
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;

// Build query conditions
$conditions = [];
$params = [];

if ($flock_id) {
    $conditions[] = "fc.flock_id = ?";
    $params[] = $flock_id;
}

if ($feed_type) {
    $conditions[] = "fc.feed_type = ?";
    $params[] = $feed_type;
}

if ($start_date) {
    $conditions[] = "fc.feeding_date >= ?";
    $params[] = $start_date;
}

if ($end_date) {
    $conditions[] = "fc.feeding_date <= ?";
    $params[] = $end_date;
}

$where_clause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

// Get total records
$total_query = "SELECT COUNT(*) FROM feed_consumption fc $where_clause";
$total_stmt = $pdo->prepare($total_query);
$total_stmt->execute($params);
$total_records = $total_stmt->fetchColumn();

// Calculate total pages
$total_pages = ceil($total_records / $items_per_page);

// Get records
$query = "
    SELECT 
        fc.*,
        f.name as flock_name,
        u.username as recorded_by_name
    FROM feed_consumption fc
    JOIN flocks f ON fc.flock_id = f.id
    JOIN users u ON fc.recorded_by = u.id
    $where_clause
    ORDER BY fc.feeding_date DESC, fc.id DESC
    LIMIT $items_per_page OFFSET $offset
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$records = $stmt->fetchAll();

// Get flocks for filter dropdown
$flocks = db_query($pdo, "SELECT id, name FROM flocks ORDER BY name");

// Get feed types for filter dropdown
$feed_types = db_query($pdo, "SELECT DISTINCT feed_type FROM feed_inventory ORDER BY feed_type");

// Include header
include '../../includes/header.php';
?>

<!-- Feed Consumption Records -->
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Feed Consumption Records</h1>
        <div>
            <a href="record_consumption.php" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-plus mr-2"></i> Record Consumption
            </a>
            <a href="index.php" class="ml-2 bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-arrow-left mr-2"></i> Back to Feed Management
            </a>
        </div>
    </div>
    
    <?php display_flash_message(); ?>
    
    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Filter Records</h2>
        <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label for="flock_id" class="block text-sm font-medium text-gray-700 mb-1">Flock</label>
                <select id="flock_id" name="flock_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <option value="">All Flocks</option>
                    <?php foreach ($flocks as $flock): ?>
                        <option value="<?php echo $flock['id']; ?>" <?php echo ($flock_id == $flock['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($flock['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="feed_type" class="block text-sm font-medium text-gray-700 mb-1">Feed Type</label>
                <select id="feed_type" name="feed_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <option value="">All Feed Types</option>
                    <?php foreach ($feed_types as $type): ?>
                        <option value="<?php echo $type['feed_type']; ?>" <?php echo ($feed_type == $type['feed_type']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type['feed_type']); ?>
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
            
            <div class="md:col-span-2 lg:col-span-4 flex items-center space-x-4">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-filter mr-2"></i> Apply Filters
                </button>
                <a href="consumption_records.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                    <i class="fas fa-times mr-2"></i> Clear Filters
                </a>
            </div>
        </form>
    </div>
    
    <!-- Records Table -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Consumption Records</h2>
        
        <?php if ($total_records > 0): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Flock</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Feed Type</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity (kg)</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recorded By</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($records as $record): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo $record['id']; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo format_date($record['feeding_date']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($record['flock_name']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($record['feed_type']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo number_format($record['quantity'], 2); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($record['recorded_by_name']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="view_consumption.php?id=<?php echo $record['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <?php if (has_permission('manage_feed')): ?>
                                        <a href="edit_consumption.php?id=<?php echo $record['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="delete_consumption.php?id=<?php echo $record['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this record?');">
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
                    $url_pattern = 'consumption_records.php?page=:page';
                    if ($flock_id) $url_pattern .= '&flock_id=' . $flock_id;
                    if ($feed_type) $url_pattern .= '&feed_type=' . urlencode($feed_type);
                    if ($start_date) $url_pattern .= '&start_date=' . $start_date;
                    if ($end_date) $url_pattern .= '&end_date=' . $end_date;
                    
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
                            No feed consumption records found. Please adjust your filters or add new records.
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Include footer
include '../../includes/footer.php';
?>

