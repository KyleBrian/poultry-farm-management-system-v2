<?php
/**
 * File: modules/payroll/view.php
 * View payroll details
 * @version 1.0.2
 * @integration_verification PMSFV-049
 */
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/functions.php';

// Check if user is logged in
check_login();

// Check permission
if (!has_permission('manage_payroll')) {
    set_flash_message('error', 'You do not have permission to view payroll details.');
    header("Location: " . BASE_URL . "dashboard.php");
    exit();
}

// Check if payroll ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    set_flash_message('error', 'Invalid payroll ID.');
    header("Location: index.php");
    exit();
}

$payroll_id = intval($_GET['id']);

// Get payroll details
$payroll = db_query_row($pdo, "
    SELECT p.*, e.employee_code, e.first_name, e.last_name, e.position, e.department, e.hire_date, 
           u.username as processed_by_name
    FROM payroll p
    JOIN employees e ON p.employee_id = e.id
    JOIN users u ON p.processed_by = u.id
    WHERE p.id = ?
", [$payroll_id]);

if (!$payroll) {
    set_flash_message('error', 'Payroll record not found.');
    header("Location: index.php");
    exit();
}

// Format month and year for display
$month_name = date('F', mktime(0, 0, 0, $payroll['month'], 1, $payroll['year']));
$period = $month_name . ' ' . $payroll['year'];

// Page title
$page_title = "Payroll Details for " . $payroll['first_name'] . ' ' . $payroll['last_name'] . " - " . $period;

// Mark payroll as paid
if (isset($_GET['mark_paid']) && has_permission('manage_payroll')) {
    try {
        // Update payment status
        $data = [
            'payment_status' => 'paid',
            'payment_date' => date('Y-m-d')
        ];
        
        $result = db_update($pdo, 'payroll', $data, 'id = ?', [$payroll_id]);
        
        if ($result !== false) {
            // Log activity
            log_activity($pdo, $_SESSION['user_id'], 'mark_payroll_paid', "Marked payroll #$payroll_id as paid");
            
            set_flash_message('success', "Payroll has been marked as paid.");
            header("Location: view.php?id=$payroll_id");
            exit();
        } else {
            throw new Exception("Failed to update payroll status.");
        }
    } catch (Exception $e) {
        set_flash_message('error', $e->getMessage());
        header("Location: view.php?id=$payroll_id");
        exit();
    }
}

// Include header
include '../../includes/header.php';
?>

<!-- View Payroll Details -->
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Payroll Details</h1>
        <div>
            <?php if ($payroll['payment_status'] == 'pending' && has_permission('manage_payroll')): ?>
                <a href="view.php?id=<?php echo $payroll_id; ?>&mark_paid=1" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded mr-2" onclick="return confirm('Are you sure you want to mark this payroll as paid?');">
                    <i class="fas fa-check mr-2"></i> Mark as Paid
                </a>
            <?php endif; ?>
            <a href="index.php?month=<?php echo $payroll['month']; ?>&year=<?php echo $payroll['year']; ?>" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-arrow-left mr-2"></i> Back to Payroll
            </a>
        </div>
    </div>
    
    <?php display_flash_message(); ?>
    
    <!-- Employee Information -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Employee Information</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div>
                <h3 class="text-sm font-medium text-gray-500">Employee Name</h3>
                <p class="mt-1 text-lg text-gray-900"><?php echo htmlspecialchars($payroll['first_name'] . ' ' . $payroll['last_name']); ?></p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Employee ID</h3>
                <p class="mt-1 text-lg text-gray-900"><?php echo htmlspecialchars($payroll['employee_code']); ?></p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Position</h3>
                <p class="mt-1 text-lg text-gray-900"><?php echo htmlspecialchars($payroll['position']); ?></p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Department</h3>
                <p class="mt-1 text-lg text-gray-900"><?php echo htmlspecialchars($payroll['department']); ?></p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Hire Date</h3>
                <p class="mt-1 text-lg text-gray-900"><?php echo format_date($payroll['hire_date']); ?></p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Pay Period</h3>
                <p class="mt-1 text-lg text-gray-900"><?php echo $period; ?></p>
            </div>
        </div>
    </div>
    
    <!-- Payroll Details -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Payroll Details</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div>
                <h3 class="text-sm font-medium text-gray-500">Gross Salary</h3>
                <p class="mt-1 text-lg text-gray-900"><?php echo format_currency($payroll['gross_salary']); ?></p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Tax Deduction</h3>
                <p class="mt-1 text-lg text-gray-900"><?php echo format_currency($payroll['tax_deduction']); ?></p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Insurance Deduction</h3>
                <p class="mt-1 text-lg text-gray-900"><?php echo format_currency($payroll['insurance_deduction']); ?></p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Other Deductions</h3>
                <p class="mt-1 text-lg text-gray-900"><?php echo format_currency($payroll['other_deductions']); ?></p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Total Deductions</h3>
                <p class="mt-1 text-lg text-gray-900"><?php echo format_currency($payroll['total_deductions']); ?></p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Bonus</h3>
                <p class="mt-1 text-lg text-gray-900"><?php echo format_currency($payroll['bonus']); ?></p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Net Salary</h3>
                <p class="mt-1 text-lg font-bold text-green-600"><?php echo format_currency($payroll['net_salary']); ?></p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Payment Status</h3>
                <p class="mt-1">
                    <?php if ($payroll['payment_status'] == 'paid'): ?>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                            Paid
                        </span>
                    <?php else: ?>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                            Pending
                        </span>
                    <?php endif; ?>
                </p>
            </div>
            <?php if ($payroll['payment_status'] == 'paid' && !empty($payroll['payment_date'])): ?>
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Payment Date</h3>
                    <p class="mt-1 text-lg text-gray-900"><?php echo format_date($payroll['payment_date']); ?></p>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($payroll['notes'])): ?>
            <div class="mt-6">
                <h3 class="text-sm font-medium text-gray-500">Notes</h3>
                <div class="mt-1 p-4 bg-gray-50 rounded-md">
                    <p class="text-gray-900"><?php echo nl2br(htmlspecialchars($payroll['notes'])); ?></p>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Processing Information -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Processing Information</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="text-sm font-medium text-gray-500">Processed By</h3>
                <p class="mt-1 text-lg text-gray-900"><?php echo htmlspecialchars($payroll['processed_by_name']); ?></p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Processed At</h3>
                <p class="mt-1 text-lg text-gray-900"><?php echo format_datetime($payroll['processed_at']); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Print Payslip Button -->
    <div class="mt-6 flex justify-end">
        <a href="print_payslip.php?id=<?php echo $payroll_id; ?>" target="_blank" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
            <i class="fas fa-print mr-2"></i> Print Payslip
        </a>
    </div>
</div>

<?php
// Include footer
include '../../includes/footer.php';
?>

