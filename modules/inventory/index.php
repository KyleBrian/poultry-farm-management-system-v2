<?php
/**
 * File: modules/inventory/index.php
 * Inventory management dashboard
 * @version 1.0.2
 * @integration_verification PMSFV-057
 */
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/functions.php';

// Check if user is logged in
check_login();

// Page title
$page_title = "Inventory Management";

// Handle inventory item deletion
if (isset($_GET['delete']) && has_permission('manage_inventory')) {
    $item_id = intval($_GET['delete']);
    
    try {
        // Check if item exists
        $item = db_query_row($pdo, "SELECT * FROM inventory WHERE id = ?", [$item_id]);
        
        if (!$item) {
            throw new Exception("Inventory item not found.");
        }
        
        // Delete item
        db_delete($pdo, 'inventory', 'id = ?', [$item_id]);
        
        // Log activity
        log_activity($pdo, $_SESSION['user_id'], 'delete_inventory', "Deleted inventory item: {$item['item_name']}");
        
        set_flash_message('success', "Inventory item deleted successfully.");
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
$category = isset($_GET['category']) ? $_GET['category'] : null;
$status = isset($_GET['status']) ? $_GET['status'] : null;
$search = isset($_GET['search']) ? $_GET['search'] : null;

// Build query conditions
$conditions = [];
$params = [];

if ($category) {
    $conditions[] = "category = ?";
    $params[] = $category;
}

if ($status) {
    $conditions[] = "status = ?";
    $params[] = $status;
}

if ($search) {
    $conditions[] = "(item_name LIKE ? OR supplier LIKE ? OR storage_location LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

// Get total records
$total_query = "SELECT COUNT(*) FROM inventory $where_clause";
$total_stmt = $pdo->prepare($total_query);
$total_stmt->execute($params);
$total_records = $total_stmt->fetchColumn();

// Calculate total pages
$total_pages = ceil($total_records / $items_per_page);

// Get inventory items
$query = "
    SELECT i.*, u.username as created_by_name
    FROM inventory i
    JOIN users u ON i.created_by = u.id
    $where_clause
    ORDER BY i.purchase_date DESC, i.id DESC
    LIMIT $items_per_page OFFSET $offset
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$inventory_items = $stmt->fetchAll();

// Get inventory statistics
$inventory_stats = db_query_row($pdo, "
    SELECT 
        COUNT(*) as total_items,
        SUM(CASE WHEN status = 'in_stock' THEN 1 ELSE 0 END) as in_stock_items,
        SUM(CASE WHEN status = 'in_stock' THEN quantity ELSE 0 END) as total_quantity,
        SUM(CASE WHEN status = 'in_stock' THEN quantity * unit_cost ELSE 0 END) as total_value
    FROM inventory
");

// Get categories for filter dropdown
$categories = db_query($pdo, "SELECT DISTINCT category FROM inventory ORDER BY category");

// Get low stock items
$low_stock_items = db_query($pdo, "
    SELECT *
    FROM inventory
    WHERE status = 'in_stock' AND quantity <= reorder_point AND reorder_point > 0
    ORDER BY (quantity / reorder_point) ASC
    LIMIT 5
");

// Get expiring items
$expiring_items = db_query($pdo, "
    SELECT *
    FROM inventory
    WHERE status = 'in_stock' AND expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ORDER BY expiry_date ASC
    LIMIT 5
");

// Include header
include '../../includes/header.php';
?>

<!-- Inventory Management -->
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Inventory Management</h1>
        <?php if (has_permission('manage_inventory')): ?>
            <a href="add.php" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-plus mr-2"></i> Add Inventory Item
            </a>
        <?php endif; ?>
    </div>
    
    <?php display_flash_message(); ?>
    
    <!-- Inventory Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-2">Total Items</h2>
            <p class="text-3xl font-bold text-blue-600"><?php echo number_format($inventory_stats['total_items']); ?></p>
            <p class="text-sm text-gray-500 mt-1"><?php echo number_format($inventory_stats['in_stock_items']); ?> in stock</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-2">Total Value</h2>
            <p class="text-3xl font-bold text-green-600"><?php echo format_currency($inventory_stats['total_value']); ?></p>
            <p class="text-sm text-gray-500 mt-1">Current inventory value</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-2">Low Stock Items</h2>
            <p class="text-3xl font-bold text-yellow-600"><?php echo count($low_stock_items); ?></p>
            <p class="text-sm text-gray-500 mt-1">Items below reorder point</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-2">Expiring Soon</h2>
            <p class="text-3xl font-bold text-red-600"><?php echo count($expiring_items); ?></p>
            <p class="text-sm text-gray-500 mt-1">Items expiring in 30 days</p>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Filter Inventory</h2>
        <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <select id="category" name="category" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat['category']); ?>" <?php echo ($category == $cat['category']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['category']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="status" name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <option value="">All Statuses</option>
                    <option value="in_stock" <?php echo ($status == 'in_stock') ? 'selected' : ''; ?>>In Stock</option>
                    <option value="depleted" <?php echo ($status == 'depleted') ? 'selected' : ''; ?>>Depleted</option>
                    <option value="expired" <?php echo ($status == 'expired') ? 'selected' : ''; ?>>Expired</option>
                </select>
            </div>
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search ?? ''); ?>" placeholder="Search by name, supplier, etc." class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
            </div>
            <div class="flex items-end">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded mr-2">
                    <i class="fas fa-filter mr-2"></i> Apply Filters
                </button>
                <a href="index.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                    <i class="fas fa-times mr-2"></i> Clear
                </a>
            </div>
        </form>
    </div>
    
    <!-- Inventory Table -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Inventory Items</h2>
        
        <?php if (count($inventory_items) > 0): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Cost</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purchase Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($inventory_items as $item): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['item_name']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($item['category']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo number_format($item['quantity'], 2); ?> <?php echo htmlspecialchars($item['unit_of_measure']); ?>
                                        <?php if ($item['status'] == 'in_stock' && $item['quantity'] <= $item['reorder_point'] && $item['reorder_point'] > 0): ?>
                                            <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                Low Stock
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo format_currency($item['unit_cost']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo format_date($item['purchase_date']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $status_color = '';
                                    switch ($item['status']) {
                                        case 'in_stock':
                                            $status_color = 'green';
                                            break;
                                        case 'depleted':
                                            $status_color = 'gray';
                                            break;
                                        case 'expired':
                                            $status_color = 'red';
                                            break;
                                    }
                                    ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-<?php echo $status_color; ?>-100 text-<?php echo $status_color; ?>-800">
                                        <?php echo ucfirst(str_replace('_', ' ', $item['status'])); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="view.php?id=<?php echo $item['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <?php if (has_permission('manage_inventory')): ?>
                                        <a href="edit.php?id=<?php echo $item['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="index.php?delete=<?php echo $item['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this inventory item?');">
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
                    if ($category) $url_pattern .= '&category=' . urlencode($category);
                    if ($status) $url_pattern .= '&status=' . $status;
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
                            No inventory items found. Please adjust your filters or add a new inventory item.
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Low Stock and Expiring Items -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
        <!-- Low Stock Items -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Low Stock Items</h2>
            
            <?php if (count($low_stock_items) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Name</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reorder Point</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($low_stock_items as $item): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['item_name']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo number_format($item['quantity'], 2); ?> <?php echo htmlspecialchars($item['unit_of_measure']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo number_format($item['reorder_point'], 2); ?> <?php echo htmlspecialchars($item['unit_of_measure']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="view.php?id=<?php echo $item['id']; ?>" class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-gray-600">No low stock items found.</p>
            <?php endif; ?>
        </div>
        
        <!-- Expiring Items -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Expiring Soon</h2>
            
            <?php if (count($expiring_items) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Name</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expiry Date</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($expiring_items as $item): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['item_name']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $days_until_expiry = (strtotime($item['expiry_date']) - time()) / (60 * 60 * 24);
                                        $expiry_class = $days_until_expiry <= 7 ? 'text-red-600 font-bold' : 'text-yellow-600';
                                        ?>
                                        <div class="text-sm <?php echo $expiry_class; ?>">
                                            <?php echo format_date($item['expiry_date']); ?>
                                            (<?php echo floor($days_until_expiry); ?> days)
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo number_format($item['quantity'], 2); ?> <?php echo htmlspecialchars($item['unit_of_measure']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="view.php?id=<?php echo $item['id']; ?>" class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-gray-600">No items expiring soon.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Include footer
include '../../includes/footer.php';
?>

