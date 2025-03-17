<?php
$page_title = "Generate Payroll";
require_once '../../includes/header.php';
require_once '../../includes/functions.php';

// Check if user has appropriate permissions
if (!in_array($_SESSION['user_role'], ['admin', 'manager'])) {
    $_SESSION['error_msg'] = "You don't have permission to access this page.";
    header("Location: " . BASE_URL . "dashboard.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validate dates
        $period_start = $_POST['period_start'];
        $period_end = $_POST['period_end'];
        $payment_date = $_POST['payment_date'];
        
        if (strtotime($period_end) <= strtotime($period_start)) {
            throw new Exception("End date must be after start date.");
        }
        
        if (strtotime($payment_date) < strtotime($period_end)) {
            throw new Exception("Payment date should be on or after the period end date.");
        }
        
        // Check for overlapping periods
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM payroll_periods 
            WHERE (period_start BETWEEN ? AND ? OR period_end BETWEEN ? AND ?)
            AND status != 'cancelled'
        ");
        $stmt->execute([$period_start, $period_end, $period_start, $period_end]);
        
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("There is already a payroll period that overlaps with the selected dates.");
        }
        
        // Create new payroll period
        $stmt = $pdo->prepare("
            INSERT INTO payroll_periods (period_start, period_end, payment_date, status, notes) 
            VALUES (?, ?, ?, 'draft', ?)
        ");
        $stmt->execute([$period_start, $period_end, $payment_date, $_POST['notes']]);
        
        $period_id = $pdo->lastInsertId();
        
        // Redirect to the payroll calculation page
        $_SESSION['success_msg'] = "Payroll period created successfully. Now you can add employees to this payroll.";
        header("Location: calculate.php?id=" . $period_id);
        exit();
    } catch (Exception $e) {
        $_SESSION['error_msg'] = $e->getMessage();
    }
}
?>

<div class="mb-4">
    <h1 class="text-2xl font-bold">Generate New Payroll</h1>
</div>

<?php
if (isset($_SESSION['error_msg'])) {
    echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>{$_SESSION['error_msg']}</div>";
    unset($_SESSION['error_msg']);
}
?>

<form method="POST" action="" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
    <div class="mb-6">
        <h2 class="text-lg font-semibold mb-4">Payroll Period</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="period_start">
                    Period Start Date *
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="period_start" name="period_start" type="date" required>
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="period_end">
                    Period End Date *
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="period_end" name="period_end" type="date" required>
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="payment_date">
                    Payment Date *
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="payment_date" name="payment_date" type="date" required>
            </div>
        </div>
    </div>
    
    <div class="mb-6">
        <label class="block text-gray-700 text-sm font-bold mb-2" for="notes">
            Notes
        </label>
        <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="notes" name="notes" rows="3"></textarea>
    </div>
    
    <div class="flex items-center justify-between">
        <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">
            Create Payroll Period
        </button>
        <a href="index.php" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
            Cancel
        </a>
    </div>
</form>

<?php require_once '../../includes/footer.php'; ?>

