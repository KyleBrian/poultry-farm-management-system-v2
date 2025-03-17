<?php
/**
 * File: modules/payroll/index.php
 * Payroll management dashboard
 * @version 1.0.2
 * @integration_verification PMSFV-047
 */
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/functions.php';

// Check if user is logged in
check_login();

// Check permission
if (!has_permission('manage_payroll')) {
    set_flash_message('error', 'You do not have permission to access payroll management.');
    header("Location: " . BASE_URL . "dashboard.php");
    exit();
}

// Page title
$page_title = "Payroll Management";

// Get current month and year
$current_month = date('m');
$current_year = date('Y');

// Get selected month and year from query parameters
$selected_month = isset($_GET['month']) ? intval($_GET['month']) : intval($current_month);
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : intval($current_year);

// Validate month and year
if ($selected_month < 1 || $selected_month > 12) {
    $selected_month = intval($current_month);
}

if ($selected_year < 2000 || $selected_year > 2100) {
    $selected_year = intval($current_year);
}

// Format month and year for display
$month_name = date('F', mktime(0, 0, 0, $selected_month, 1, $selected_year));
$period = $month_name . ' ' . $selected_year;

// Get payroll records for the selected month and year
$payroll_records = db_query($pdo, "
    SELECT p.*, e.employee_code, e.first_name, e.last_name, e.position, u.username as processed_by_name
    FROM payroll p
    JOIN employees e ON p.employee_id = e.id
    JOIN users u ON p.processed_by = u.id
    WHERE p.month = ? AND p.year = ?
    ORDER BY e.last_name, e.first_name
", [$selected_month, $selected_year]);

// Get payroll summary
$payroll_summary = db_query_row($pdo, "
    SELECT 
        COUNT(*) as total_employees,
        SUM(gross_salary) as total_gross,
        SUM(total_deductions) as total_deductions,
        SUM(net_salary) as total_net
    FROM payroll
    WHERE month = ? AND year = ?
", [$selected_month, $selected_year]);

// Check if payroll has been processed for the selected month
$payroll_processed = count($payroll_records) > 0;

// Get employees without payroll for the selected month
$employees_without_payroll = [];
if (!$payroll_processed) {
    $employees_without_payroll = db_query($pdo, "
        SELECT e.*
        FROM employees e
        LEFT JOIN payroll p ON e.id = p.employee_id AND p.month = ? AND p.year = ?
        WHERE e.status = 'active' AND p.id IS NULL
        ORDER BY e.last_name, e.first_name
    ", [$selected_month, $selected_year]);
}

// Include header
include '../../includes/header.php';
?>

<!-- Payroll Management -->
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Payroll Management</h1>
        <?php if (!$payroll_processed && has_permission('manage_payroll')): ?>
            <a href="process.php?month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-money-check-alt mr-2"></i> Process Payroll for <?php echo $period; ?>
            </a>
        <?php endif; ?>
    </div>
    
    <?php display_flash_message(); ?>
    
    <!-- Month/Year Selector -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Select Payroll Period</h2>
        <form method="GET" action="" class="flex flex-wrap items-center gap-4">
            <div>
                <label for="month" class="block text-sm font-medium text-gray-700 mb-1">Month</label>
                <select id="month" name="month" class="rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" onchange="this.form.submit()">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?php echo $m; ?>" <?php echo ($m == $selected_month) ? 'selected' : ''; ?>>
                            <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label for="year" class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                <select id="year" name="year" class="rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" onchange="this.form.submit()">
                    <?php for ($y = $current_year - 5; $y <= $current_year + 1; $y++): ?>
                        <option value="<?php echo $y; ?>" <?php echo ($y == $selected_year) ? 'selected' : ''; ?>>
                            <?php echo $y; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
        </form>
    </div>
    
    <?php if ($payroll_processed): ?>
        <!-- Payroll Summary -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold mb-2">Total Employees</h2>
                <p class="text-3xl font-bold text-blue-600"><?php echo number_format($payroll_summary['total_employees']); ?></p>
                <p class="text-sm text-gray-500 mt-1">Employees paid this period</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold mb-2">Gross Salary</h2>
                <p class="text-3xl font-bold text-green-600"><?php echo format_currency($payroll_summary['total_gross']); ?></p>
                <p class="text-sm text-gray-500 mt-1">Total gross salary</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold mb-2">Deductions</h2>
                <p class="text-3xl font-bold text-red-600"><?php echo format_currency($payroll_summary['total_deductions']); ?></p>
                <p class="text-sm text-gray-500 mt-1">Total deductions</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold mb-2">Net Salary</h2>
                <p class="text-3xl font-bold text-purple-600"><?php echo format_currency($payroll_summary['total_net']); ?></p>
                <p class="text-sm text-gray-500 mt-1">Total net salary paid</p>
            </div>
        </div>
        
        <!-- Payroll Records -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800">Payroll Records for <?php echo $period; ?></h2>
                <div>
                    <a href="export.php?month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded">
                        <i class="fas fa-file-export mr-2"></i> Export
                    </a>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Gross Salary</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Deductions</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Net Salary</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Status</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($payroll_records as $record): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                <?php echo htmlspecialchars($record['employee_code']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($record['position']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-sm text-gray-900"><?php echo format_currency($record['gross_salary']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-sm text-gray-900"><?php echo format_currency($record['total_deductions']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-sm font-medium text-gray-900"><?php echo format_currency($record['net_salary']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <?php if ($record['payment_status'] == 'paid'): ?>
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
                                    <a href="view.php?id=<?php echo $record['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <?php if ($record['payment_status'] == 'pending' && has_permission('manage_payroll')): ?>
                                        <a href="mark_paid.php?id=<?php echo $record['id']; ?>" class="text-green-600 hover:text-green-900" onclick="return confirm('Are you sure you want to mark this payroll as paid?');">
                                            <i class="fas fa-check"></i> Mark Paid
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <!-- Payroll Not Processed Message -->
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        Payroll has not been processed for <?php echo $period; ?> yet.
                        <?php if (has_permission('manage_payroll')): ?>
                            <a href="process.php?month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" class="font-medium underline text-yellow-700 hover:text-yellow-600">
                                Process payroll now
                            </a>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Employees List -->
        <?php if (count($employees_without_payroll) > 0): ?>
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Employees Pending Payroll for <?php echo $period; ?></h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee Code</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Base Salary</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($employees_without_payroll as $employee): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($employee['employee_code']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($employee['position']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <div class="text-sm text-gray-900"><?php echo format_currency($employee['base_salary']); ?></div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
// Include footer
include '../../includes/footer.php';
?>

