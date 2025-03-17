<?php
/**
 * File: modules/payroll/process.php
 * Process payroll
 * @version 1.0.2
 * @integration_verification PMSFV-048
 */
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/functions.php';

// Check if user is logged in
check_login();

// Check permission
if (!has_permission('manage_payroll')) {
    set_flash_message('error', 'You do not have permission to process payroll.');
    header("Location: index.php");
    exit();
}

// Get month and year from query parameters
$selected_month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// Validate month and year
if ($selected_month < 1 || $selected_month > 12) {
    $selected_month = intval(date('m'));
}

if ($selected_year < 2000 || $selected_year > 2100) {
    $selected_year = intval(date('Y'));
}

// Format month and year for display
$month_name = date('F', mktime(0, 0, 0, $selected_month, 1, $selected_year));
$period = $month_name . ' ' . $selected_year;

// Page title
$page_title = "Process Payroll for " . $period;

// Check if payroll has already been processed for this month
$existing_payroll = db_query_row($pdo, "
    SELECT COUNT(*) as count
    FROM payroll
    WHERE month = ? AND year = ?
", [$selected_month, $selected_year]);

if ($existing_payroll['count'] > 0) {
    set_flash_message('error', "Payroll has already been processed for $period.");
    header("Location: index.php?month=$selected_month&year=$selected_year");
    exit();
}

// Get all active employees
$employees = db_query($pdo, "
    SELECT *
    FROM employees
    WHERE status = 'active'
    ORDER BY last_name, first_name
");

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        // Process each employee's payroll
        foreach ($employees as $employee) {
            $employee_id = $employee['id'];
            
            // Get form data for this employee
            $gross_salary = floatval($_POST['gross_salary'][$employee_id]);
            $tax_deduction = floatval($_POST['tax_deduction'][$employee_id]);
            $insurance_deduction = floatval($_POST['insurance_deduction'][$employee_id]);
            $other_deductions = floatval($_POST['other_deductions'][$employee_id]);
            $bonus = floatval($_POST['bonus'][$employee_id]);
            $notes = $_POST['notes'][$employee_id] ?? '';
            
            // Calculate totals
            $total_deductions = $tax_deduction + $insurance_deduction + $other_deductions;
            $net_salary = $gross_salary - $total_deductions + $bonus;
            
            // Insert payroll record
            $data = [
                'employee_id' => $employee_id,
                'month' => $selected_month,
                'year' => $selected_year,
                'gross_salary' => $gross_salary,
                'tax_deduction' => $tax_deduction,
                'insurance_deduction' => $insurance_deduction,
                'other_deductions' => $other_deductions,
                'total_deductions' => $total_deductions,
                'bonus' => $bonus,
                'net_salary' => $net_salary,
                'payment_status' => 'pending',
                'payment_date' => null,
                'notes' => $notes,
                'processed_by' => $_SESSION['user_id'],
                'processed_at' => date('Y-m-d H:i:s')
            ];
            
            db_insert($pdo, 'payroll', $data);
        }
        
        // Log activity
        log_activity($pdo, $_SESSION['user_id'], 'process_payroll', "Processed payroll for $period");
        
        // Commit transaction
        $pdo->commit();
        
        set_flash_message('success', "Payroll for $period has been processed successfully.");
        header("Location: index.php?month=$selected_month&year=$selected_year");
        exit();
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

// Include header
include '../../includes/header.php';
?>

<!-- Process Payroll -->
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Process Payroll for <?php echo $period; ?></h1>
        <a href="index.php?month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
            <i class="fas fa-arrow-left mr-2"></i> Back to Payroll
        </a>
    </div>
    
    <?php display_flash_message(); ?>
    
    <?php if (isset($error)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <p><?php echo $error; ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (count($employees) > 0): ?>
        <form method="POST" action="">
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Employee Payroll Details</h2>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Gross Salary</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Tax</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Insurance</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Other Deductions</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Bonus</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Net Salary</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($employees as $employee): ?>
                                <?php
                                // Default values
                                $gross_salary = $employee['base_salary'];
                                $tax_rate = 0.15; // 15% tax rate
                                $insurance_rate = 0.05; // 5% insurance rate
                                $tax_deduction = $gross_salary * $tax_rate;
                                $insurance_deduction = $gross_salary * $insurance_rate;
                                $other_deductions = 0;
                                $bonus = 0;
                                $total_deductions = $tax_deduction + $insurance_deduction + $other_deductions;
                                $net_salary = $gross_salary - $total_deductions + $bonus;
                                ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?php echo htmlspecialchars($employee['employee_code']); ?> - <?php echo htmlspecialchars($employee['position']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="number" name="gross_salary[<?php echo $employee['id']; ?>]" value="<?php echo $gross_salary; ?>" step="0.01" min="0" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 text-right" required onchange="calculateNetSalary(<?php echo $employee['id']; ?>)">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="number" name="tax_deduction[<?php echo $employee['id']; ?>]" value="<?php echo $tax_deduction; ?>" step="0.01" min="0" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 text-right" required onchange="calculateNetSalary(<?php echo $employee['id']; ?>)">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="number" name="insurance_deduction[<?php echo $employee['id']; ?>]" value="<?php echo $insurance_deduction; ?>" step="0.01" min="0" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 text-right" required onchange="calculateNetSalary(<?php echo $employee['id']; ?>)">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="number" name="other_deductions[<?php echo $employee['id']; ?>]" value="<?php echo $other_deductions; ?>" step="0.01" min="0" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 text-right" required onchange="calculateNetSalary(<?php echo $employee['id']; ?>)">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="number" name="bonus[<?php echo $employee['id']; ?>]" value="<?php echo $bonus; ?>" step="0.01" min="0" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 text-right" required onchange="calculateNetSalary(<?php echo $employee['id']; ?>)">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 text-right" id="net_salary_<?php echo $employee['id']; ?>">
                                            <?php echo format_currency($net_salary); ?>
                                        </div>
                                        <input type="hidden" name="net_salary[<?php echo $employee['id']; ?>]" value="<?php echo $net_salary; ?>" id="net_salary_input_<?php echo $employee['id']; ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="7" class="px-6 py-2">
                                        <label for="notes_<?php echo $employee['id']; ?>" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                        <textarea id="notes_<?php echo $employee['id']; ?>" name="notes[<?php echo $employee['id']; ?>]" rows="1" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"></textarea>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="flex justify-end">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    <i class="fas fa-save mr-2"></i> Process Payroll
                </button>
            </div>
        </form>
    <?php else: ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        No active employees found. Please add employees before processing payroll.
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function calculateNetSalary(employeeId) {
    const grossSalary = parseFloat(document.getElementsByName(`gross_salary[${employeeId}]`)[0].value) || 0;
    const taxDeduction = parseFloat(document.getElementsByName(`tax_deduction[${employeeId}]`)[0].value) || 0;
    const insuranceDeduction = parseFloat(document.getElementsByName(`insurance_deduction[${employeeId}]`)[0].value) || 0;
    const otherDeductions = parseFloat(document.getElementsByName(`other_deductions[${employeeId}]`)[0].value) || 0;
    const bonus = parseFloat(document.getElementsByName(`bonus[${employeeId}]`)[0].value) || 0;
    
    const totalDeductions = taxDeduction + insuranceDeduction + otherDeductions;
    const netSalary = grossSalary - totalDeductions + bonus;
    
    // Format for display
    const formatter = new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    });
    
    document.getElementById(`net_salary_${employeeId}`).textContent = formatter.format(netSalary);
    document.getElementById(`net_salary_input_${employeeId}`).value = netSalary;
}
</script>

<?php
// Include footer
include '../../includes/footer.php';
?>

