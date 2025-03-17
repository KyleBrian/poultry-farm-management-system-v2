<?php
/**
 * File: modules/finance/invoices.php
 * Invoice management
 * @version 1.0.1
 * @integration_verification PMSFV-022
 */
$page_title = "Invoices";
require_once '../../includes/header.php';
require_once '../../includes/functions.php';

// Check if user has appropriate permissions
if (!in_array($_SESSION['user_role'], ['admin', 'manager'])) {
    $_SESSION['error_msg'] = "You don't have permission to access this page.";
    header("Location: " . BASE_URL . "dashboard.php");
    exit();
}

// Handle invoice deletion
if (isset($_GET['delete'])) {
    $invoice_id = intval($_GET['delete']);
    try {
        // Check if invoice can be deleted (only draft or cancelled invoices)
        $stmt = $pdo->prepare("SELECT status FROM invoices WHERE id = ?");
        $stmt->execute([$invoice_id]);
        $invoice = $stmt->fetch();
        
        if (!$invoice) {
            throw new Exception("Invoice not found.");
        }
        
        if (!in_array($invoice['status'], ['draft', 'cancelled'])) {
            throw new Exception("Only draft or cancelled invoices can be deleted.");
        }
        
        // Begin transaction
        $pdo->beginTransaction();
        
        // Delete invoice items
        $stmt = $pdo->prepare("DELETE FROM invoice_items WHERE invoice_id = ?");
        $stmt->execute([$invoice_id]);
        
        // Delete invoice
        $stmt = $pdo->prepare("DELETE FROM invoices WHERE id = ?");
        $stmt->execute([$invoice_id]);
        
        // Commit transaction
        $pdo->commit();
        
        $_SESSION['success_msg'] = "Invoice deleted successfully.";
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error_msg'] = $e->getMessage();
    }
    header("Location: invoices.php");
    exit();
}

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Fetch invoices with pagination
$stmt = $pdo->prepare("
    SELECT i.*, c.name as customer_name, u.username as created_by_name
    FROM invoices i
    LEFT JOIN customers c ON i.customer_id = c.id
    LEFT JOIN users u ON i.created_by = u.id
    ORDER BY i.invoice_date DESC
    LIMIT :offset, :records_per_page
");
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->bindParam(':records_per_page', $records_per_page, PDO::PARAM_INT);
$stmt->execute();
$invoices = $stmt->fetchAll();

// Get total number of records
$stmt = $pdo->query("SELECT COUNT(*) FROM invoices");
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $records_per_page);

// Get invoice statistics
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_invoices,
        SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_invoices,
        SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue_invoices,
        SUM(total_amount) as total_amount,
        SUM(paid_amount) as paid_amount
    FROM invoices
");
$invoice_stats = $stmt->fetch();
?>

<div class="mb-4 flex justify-between items-center">
    <h1 class="text-2xl font-bold">Invoices</h1>
    <a href="create_invoice.php" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
        <i class="fas fa-plus mr-2"></i>Create Invoice
    </a>
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

<!-- Invoice Statistics -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-2">Total Invoices</h2>
        <p class="text-3xl font-bold text-blue-600"><?php echo number_format($invoice_stats['total_invoices']); ?></p>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-2">Paid Invoices</h2>
        <p class="text-3xl font-bold text-green-600"><?php echo number_format($invoice_stats['paid_invoices']); ?></p>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-2">Overdue Invoices</h2>
        <p class="text-3xl font-bold text-red-600"><?php echo number_format($invoice_stats['overdue_invoices']); ?></p>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-2">Outstanding Amount</h2>
        <p class="text-3xl font-bold text-yellow-600"><?php echo formatCurrency($invoice_stats['total_amount'] - $invoice_stats['paid_amount']); ?></p>
    </div>
</div>

<!-- Invoices Table -->
<div class="bg-white shadow-md rounded my-6">
    <table class="min-w-max w-full table-auto">
        <thead>
            <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                <th class="py-3 px-6 text-left">Invoice #</th>
                <th class="py-3 px-6 text-left">Customer</th>
                <th class="py-3 px-6 text-center">Date</th>
                <th class="py-3 px-6 text-center">Due Date</th>
                <th class="py-3 px-6 text-right">Amount</th>
                <th class="py-3 px-6 text-center">Status</th>
                <th class="py-3 px-6 text-center">Actions</th>
            </tr>
        </thead>
        <tbody class="text-gray-600 text-sm font-light">
            <?php if (count($invoices) > 0): ?>
                <?php foreach ($invoices as $invoice): ?>
                    <tr class="border-b border-gray-200 hover:bg-gray-100">
                        <td class="py-3 px-6 text-left whitespace-nowrap">
                            <span class="font-medium"><?php echo htmlspecialchars($invoice['invoice_number']); ?></span>
                        </td>
                        <td class="py-3 px-6 text-left">
                            <span><?php echo htmlspecialchars($invoice['customer_name']); ?></span>
                        </td>
                        <td class="py-3 px-6 text-center">
                            <?php echo formatDate($invoice['invoice_date']); ?>
                        </td>
                        <td class="py-3 px-6 text-center">
                            <?php echo formatDate($invoice['due_date']); ?>
                        </td>
                        <td class="py-3 px-6 text-right">
                            <?php echo formatCurrency($invoice['total_amount']); ?>
                        </td>
                        <td class="py-3 px-6 text-center">
                            <?php
                            $status_color = '';
                            switch ($invoice['status']) {
                                case 'draft':
                                    $status_color = 'gray';
                                    break;
                                case 'sent':
                                    $status_color = 'blue';
                                    break;
                                case 'paid':
                                    $status_color = 'green';
                                    break;
                                case 'overdue':
                                    $status_color = 'red';
                                    break;
                                case 'cancelled':
                                    $status_color = 'yellow';
                                    break;
                            }
                            ?>
                            <span class="bg-<?php echo $status_color; ?>-200 text-<?php echo $status_color; ?>-600 py-1 px-3 rounded-full text-xs">
                                <?php echo ucfirst($invoice['status']); ?>
                            </span>
                        </td>
                        <td class="py-3 px-6 text-center">
                            <div class="flex item-center justify-center">
                                <a href="view_invoice.php?id=<?php echo $invoice['id']; ?>" class="w-4 mr-2 transform hover:text-purple-500 hover:scale-110">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if (in_array($invoice['status'], ['draft', 'sent'])): ?>
                                    <a href="edit_invoice.php?id=<?php echo $invoice['id']; ?>" class="w-4 mr-2 transform hover:text-purple-500 hover:scale-110">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if ($invoice['status'] == 'sent'): ?>
                                    <a href="mark_paid.php?id=<?php echo $invoice['id']; ?>" class="w-4 mr-2 transform hover:text-green-500 hover:scale-110" title="Mark as Paid">
                                        <i class="fas fa-check"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (in_array($invoice['status'], ['draft', 'cancelled'])): ?>
                                    <a href="invoices.php?delete=<?php echo $invoice['id']; ?>" class="w-4 mr-2 transform hover:text-red-500 hover:scale-110" onclick="return confirm('Are you sure you want to delete this invoice?');">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr class="border-b border-gray-200">
                    <td class="py-3 px-6 text-center" colspan="7">No invoices found</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
    <div class="flex justify-center mt-4">
        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                    <span class="sr-only">Previous</span>
                    <i class="fas fa-chevron-left"></i>
                </a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium <?php echo $page == $i ? 'text-blue-600 bg-blue-50' : 'text-gray-700 hover:bg-gray-50'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                    <span class="sr-only">Next</span>
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </nav>
    </div>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>

