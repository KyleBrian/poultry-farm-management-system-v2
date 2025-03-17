<?php
/**
 * File: modules/sales/index.php
 * Sales management dashboard
 * @version 1.0.2
 * @integration_verification PMSFV-050
 */
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/functions.php';

// Check if user is logged in
check_login();

// Page title
$page_title = "Sales Management";

// Handle sale deletion
if (isset($_GET['delete']) && has_permission('manage_sales')) {
    $sale_id = intval($_GET['delete']);
    
    try {
        // Check if sale exists
        $sale = db_query_row($pdo, "SELECT * FROM sales WHERE id = ?", [$sale_id]);
        
        if (!$sale) {
            throw new Exception("Sale not found.");
        }
        
        // Begin transaction
        $pdo->beginTransaction();
        
        // Delete sale items
        db_delete($pdo, 'sale_items', 'sale_id = ?', [$sale_id]);
        
        // Delete sale
        db_delete($pdo, 'sales', 'id = ?', [$sale_id]);
        
        // Log activity
        log_activity($pdo, $_SESSION['user_id'], 'delete_sale', "Deleted sale #$sale_id");
        
        // Commit transaction
        $pdo->commit();
        
        set_flash_message('success', "Sale deleted successfully.");
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
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
$customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : null;
$payment_status = isset($_GET['payment_status']) ? $_GET['payment_status'] : null;
$payment_method = isset($_GET['payment_method']) ? $_GET['payment_method'] : null;
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;
$search = isset($_GET['search']) ? $_GET['search'] : null;

// Build query conditions
$conditions = [];
$params = [];

if ($customer_id) {
    $conditions[] = "s.customer_id = ?";
    $params[] = $customer_id;
}

if ($payment_status) {
    $conditions[] = "s.payment_status = ?";
    $params[] = $payment_status;
}

if ($payment_method) {
    $conditions[] = "s.payment_method = ?";
    $params[] = $payment_method;
}

if ($start_date) {
    $conditions[] = "s.sale_date >= ?";
    $params[] = $start_date;
}

if ($end_date) {
    $conditions[] = "s.sale_date <= ?";
    $params[] = $end_date;
}

if ($search) {
    $conditions[] = "(s.customer_name LIKE ? OR s.customer_contact LIKE ? OR s.notes LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

// Get total records
$total_query = "
    SELECT COUNT(*) 
    FROM sales s
    $where_clause
";
$total_stmt = $pdo->prepare($total_query);
$total_stmt->execute($params);
$total_records = $total_stmt->fetchColumn();

// Calculate total pages
$total_pages = ceil($total_records / $items_per_page);

// Get sales
$query = "
    SELECT s.*, u.username as created_by_name
    FROM sales s
    JOIN users u ON s.created_by = u.id
    $where_clause
    ORDER BY s.sale_date DESC, s.id DESC
    LIMIT $items_per_page OFFSET $offset
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$sales = $stmt->fetchAll();

// Get sales statistics
$sales_stats = db_query_row($pdo, "
    SELECT 
        COUNT(*) as total_sales,
        SUM(total_amount) as total_revenue,
        AVG(total_amount) as average_sale,
        SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as paid_amount,
        SUM(CASE WHEN payment_status = 'pending' THEN total_amount ELSE 0 END) as pending_amount
    FROM sales
    " . ($where_clause ? $where_clause : ""),
    $params
);

// Get customers for filter dropdown
$customers = db_query($pdo, "SELECT id, name FROM customers ORDER BY name");

// Include header
include '../../includes/header.php';
?>

&lt;!-- Sales Management -->
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Sales Management</h1>
        <?php if (has_permission('manage_sales')): ?>
            <a href="add.php" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-plus mr-2"></i> Add New Sale
            </a>
        <?php endif; ?>
    </div>
    
    <?php display_flash_message(); ?>
    
    &lt;!-- Sales Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-2">Total Sales</h2>
            <p class="text-3xl font-bold text-blue-600"><?php echo number_format($sales_stats['total_sales']); ?></p>
            <p class="text-sm text-gray-500 mt-1">Sales records</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-2">Total Revenue</h2>
            <p class="text-3xl font-bold text-green-600"><?php echo format_currency($sales_stats['total_revenue']); ?></p>
            <p class="text-sm text-gray-500 mt-1">Revenue generated</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-2">Average Sale</h2>
            <p class="text-3xl font-bold text-purple-600"><?php echo format_currency($sales_stats['average_sale']); ?></p>
            <p class="text-sm text-gray-500 mt-1">Average sale amount</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-2">Pending Payments</h2>
            <p class="text-3xl font-bold text-yellow-600"><?php echo format_currency($sales_stats['pending_amount']); ?></p>
            <p class="text-sm text-gray-500 mt-1">Unpai  ?></p>
            <p class="text-sm text-gray-500 mt-1">Unpaid sales amount</p>
        </div>
    </div>
    
    &lt;!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Filter Sales</h2>
        <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="customer_id" class="block text-sm font-medium text-gray-700 mb-1">Customer</label>
                <select id="customer_id" name="customer_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <option value="">All Customers</option>
                    <?php foreach ($customers as $customer): ?>
                        <option value="<?php echo $customer['id']; ?>" <?php echo ($customer_id == $customer['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($customer['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="payment_status" class="block text-sm font-medium text-gray-700 mb-1">Payment Status</label>
                <select id="payment_status" name="payment_status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <option value="">All Statuses</option>
                    <option value="paid" <?php echo ($payment_status == 'paid') ? 'selected' : ''; ?>>Paid</option>
                    <option value="pending" <?php echo ($payment_status == 'pending') ? 'selected' : ''; ?>>Pending</option>
                </select>
            </div>
            <div>
                <label for="payment_method" class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                <select id="payment_method" name="payment_method" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <option value="">All Methods</option>
                    <option value="cash" <?php echo ($payment_method == 'cash') ? 'selected' : ''; ?>>Cash</option>
                    <option value="bank_transfer" <?php echo ($payment_method == 'bank_transfer') ? 'selected' : ''; ?>>Bank Transfer</option>
                    <option value="check" <?php echo ($payment_method == 'check') ? 'selected' : ''; ?>>Check</option>
                    <option value="credit_card" <?php echo ($payment_method == 'credit_card') ? 'selected' : ''; ?>>Credit Card</option>
                    <option value="mobile_money" <?php echo ($payment_method == 'mobile_money') ? 'selected' : ''; ?>>Mobile Money</option>
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
                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search ?? ''); ?>" placeholder="Search customer, contact, etc." class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
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
    
    &lt;!-- Sales Table -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Sales Records</h2>
        
        <?php if (count($sales) > 0): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sale Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Method</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($sales as $sale): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo format_date($sale['sale_date']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($sale['customer_name']); ?></div>
                                    <?php if (!empty($sale['customer_contact'])): ?>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($sale['customer_contact']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-sm font-medium text-gray-900"><?php echo format_currency($sale['total_amount']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="text-sm text-gray-900"><?php echo ucfirst(str_replace('_', ' ', $sale['payment_method'])); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <?php if ($sale['payment_status'] == 'paid'): ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            Paid
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            Pending
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <a href="view.php?id=<?php echo $sale['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <?php if (has_permission('manage_sales')): ?>
                                        <?php if ($sale['payment_status'] == 'pending'): ?>
                                            <a href="mark_paid.php?id=<?php echo $sale['id']; ?>" class="text-green-600 hover:text-green-900 mr-3" onclick="return confirm('Are you sure you want to mark this sale as paid?');">
                                                <i class="fas fa-check"></i> Mark Paid
                                            </a>
                                        <?php endif; ?>
                                        <a href="index.php?delete=<?php echo $sale['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this sale?');">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            &lt;!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="mt-6">
                    <?php
                    $url_pattern = 'index.php?page=:page';
                    if ($customer_id) $url_pattern .= '&customer_id=' . $customer_id;
                    if ($payment_status) $url_pattern .= '&payment_status=' . $payment_status;
                    if ($payment_method) $url_pattern .= '&payment_method=' . $payment_method;
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
                            No sales found. Please adjust your filters or add a new sale.
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    &lt;!-- Export Options -->
    <div class="mt-6 flex justify-end">
        <a href="export.php<?php echo !empty($where_clause) ? '?' . http_build_query($_GET) : ''; ?>" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded">
            <i class="fas fa-file-export mr-2"></i> Export Sales Data
        </a>
    </div>
</div>

<?php
// Include footer
include '../../includes/footer.php';
?>

