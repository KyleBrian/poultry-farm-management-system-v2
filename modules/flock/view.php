<?php
/**
 * File: modules/flock/view.php
 * View flock details
 * @version 1.0.2
 * @integration_verification PMSFV-022
 */
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/functions.php';

// Check if user is logged in
check_login();

// Check if flock ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    set_flash_message('error', 'Invalid flock ID.');
    header("Location: index.php");
    exit();
}

$flock_id = intval($_GET['id']);

// Get flock details
$flock = db_query_row($pdo, "
    SELECT f.*, u.username as created_by_name
    FROM flocks f
    LEFT JOIN users u ON f.created_by = u.id
    WHERE f.id = ?
", [$flock_id]);

if (!$flock) {
    set_flash_message('error', 'Flock not found.');
    header("Location: index.php");
    exit();
}

// Calculate age in weeks
$acquisition_date = new DateTime($flock['acquisition_date']);
$current_date = new DateTime();
$age_days = $current_date->diff($acquisition_date)->days;
$age_weeks = floor($age_days / 7);

// Calculate current bird count
$current_count = $flock['initial_count'] - $flock['mortality'];

// Get egg production data
$egg_production = db_query($pdo, "
    SELECT 
        SUM(quantity) as total_eggs,
        AVG(quantity) as avg_daily_eggs,
        COUNT(DISTINCT collection_date) as days_recorded
    FROM egg_production
    WHERE flock_id = ?
", [$flock_id]);

// Get feed consumption data
$feed_consumption = db_query_row($pdo, "
    SELECT 
        SUM(quantity) as total_feed,
        AVG(quantity) as avg_daily_feed
    FROM feed_consumption
    WHERE flock_id = ?
", [$flock_id]);

// Get health records
$health_records = db_query($pdo, "
    SELECT hr.*, u.username as recorded_by_name
    FROM health_records hr
    JOIN users u ON hr.recorded_by = u.id
    WHERE hr.flock_id = ?
    ORDER BY hr.record_date DESC
    LIMIT 5
", [$flock_id]);

// Get vaccination records
$vaccination_records = db_query($pdo, "
    SELECT vr.*, v.name as vaccine_name, u.username as administered_by_name
    FROM vaccination_records vr
    JOIN vaccines v ON vr.vaccine_id = v.id
    JOIN users u ON vr.administered_by = u.id
    WHERE vr.flock_id = ?
    ORDER BY vr.vaccination_date DESC
    LIMIT 5
", [$flock_id]);

// Page title
$page_title = "Flock Details: " . $flock['name'];

// Include header
include '../../includes/header.php';
?>

<!-- View Flock -->
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800"><?php echo htmlspecialchars($flock['name']); ?></h1>
        <div>
            <?php if (has_permission('manage_flock')): ?>
                <a href="edit.php?id=<?php echo $flock_id; ?>" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded mr-2">
                    <i class="fas fa-edit mr-2"></i> Edit Flock
                </a>
            <?php endif; ?>
            <a href="index.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-arrow-left mr-2"></i> Back to Flocks
            </a>
        </div>
    </div>
    
    <?php display_flash_message(); ?>
    
    <!-- Flock Overview -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <!-- Flock Details -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Flock Information</h2>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">Breed:</span>
                    <span class="font-medium"><?php echo htmlspecialchars($flock['breed']); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Purpose:</span>
                    <span class="font-medium"><?php echo ucfirst($flock['purpose']); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Acquisition Date:</span>
                    <span class="font-medium"><?php echo format_date($flock['acquisition_date']); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Age:</span>
                    <span class="font-medium"><?php echo $age_weeks; ?> weeks</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Status:</span>
                    <span class="font-medium">
                        <?php
                        $status_color = '';
                        switch ($flock['status']) {
                            case 'active':
                                $status_color = 'green';
                                break;
                            case 'sold':
                                $status_color = 'blue';
                                break;
                            case 'culled':
                                $status_color = 'yellow';
                                break;
                            case 'deceased':
                                $status_color = 'red';
                                break;
                        }
                        ?>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-<?php echo $status_color; ?>-100 text-<?php echo $status_color; ?>-800">
                            <?php echo ucfirst($flock['status']); ?>
                        </span>
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Created By:</span>
                    <span class="font-medium"><?php echo htmlspecialchars($flock['created_by_name']); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Created At:</span>
                    <span class="font-medium"><?php echo format_datetime($flock['created_at']); ?></span>
                </div>
            </div>
            
            <?php if (!empty($flock['notes'])): ?>
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <h3 class="text-sm font-medium text-gray-500 mb-2">Notes</h3>
                    <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($flock['notes'])); ?></p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Bird Count -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Bird Count</h2>
            <div class="flex flex-col items-center">
                <div class="text-5xl font-bold text-blue-600 mb-2"><?php echo number_format($current_count); ?></div>
                <div class="text-gray-500 mb-4">Current Birds</div>
                
                <div class="w-full bg-gray-200 rounded-full h-2.5 mb-4">
                    <?php $percent_remaining = ($current_count / $flock['initial_count']) * 100; ?>
                    <div class="bg-blue-600 h-2.5 rounded-full" style="width: <?php echo $percent_remaining; ?>%"></div>
                </div>
                
                <div class="grid grid-cols-2 w-full gap-4 text-center">
                    <div>
                        <div class="text-2xl font-semibold text-gray-800"><?php echo number_format($flock['initial_count']); ?></div>
                        <div class="text-sm text-gray-500">Initial Count</div>
                    </div>
                    <div>
                        <div class="text-2xl font-semibold text-red-600"><?php echo number_format($flock['mortality']); ?></div>
                        <div class="text-sm text-gray-500">Mortality</div>
                    </div>
                </div>
                
                <?php if ($flock['status'] == 'active'): ?>
                    <div class="mt-6 w-full">
                        <a href="../health/add_record.php?flock_id=<?php echo $flock_id; ?>&record_type=mortality" class="block w-full bg-red-500 hover:bg-red-600 text-white text-center font-bold py-2 px-4 rounded">
                            <i class="fas fa-plus mr-2"></i> Record Mortality
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Production Summary -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Production Summary</h2>
            
            <?php if ($flock['purpose'] == 'layers'): ?>
                <div class="space-y-4">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 mb-1">Total Eggs Collected</h3>
                        <p class="text-3xl font-bold text-green-600"><?php echo number_format($egg_production[0]['total_eggs'] ?? 0); ?></p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 mb-1">Average Daily Production</h3>
                        <p class="text-2xl font-semibold text-gray-800"><?php echo number_format($egg_production[0]['avg_daily_eggs'] ?? 0, 1); ?> eggs</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 mb-1">Production Rate</h3>
                        <?php
                        $production_rate = 0;
                        if (($egg_production[0]['days_recorded'] ?? 0) > 0 && $current_count > 0) {
                            $production_rate = (($egg_production[0]['avg_daily_eggs'] ?? 0) / $current_count) * 100;
                        }
                        ?>
                        <p class="text-2xl font-semibold text-gray-800"><?php echo number_format($production_rate, 1); ?>%</p>
                    </div>
                    
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <div class="flex justify-between">
                            <a href="../egg_production/index.php?flock_id=<?php echo $flock_id; ?>" class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-egg mr-1"></i> View Egg Production
                            </a>
                            <?php if ($flock['status'] == 'active'): ?>
                                <a href="../egg_production/add.php?flock_id=<?php echo $flock_id; ?>" class="text-green-600 hover:text-green-800">
                                    <i class="fas fa-plus mr-1"></i> Record Collection
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-info-circle text-yellow-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                Egg production tracking is not applicable for <?php echo ucfirst($flock['purpose']); ?> flocks.
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="mt-6">
                <h3 class="text-sm font-medium text-gray-500 mb-1">Total Feed Consumed</h3>
                <p class="text-2xl font-semibold text-gray-800"><?php echo number_format($feed_consumption['total_feed'] ?? 0, 1); ?> kg</p>
                
                <?php if ($flock['status'] == 'active'): ?>
                    <div class="mt-4">
                        <a href="../feed/add_consumption.php?flock_id=<?php echo $flock_id; ?>" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-plus mr-1"></i> Record Feed Consumption
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Health and Vaccination Records -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- Health Records -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800">Health Records</h2>
                <div>
                    <a href="../health/index.php?flock_id=<?php echo $flock_id; ?>" class="text-blue-600 hover:text-blue-800">
                        View All
                    </a>
                    <?php if ($flock['status'] == 'active'): ?>
                        <a href="../health/add_record.php?flock_id=<?php echo $flock_id; ?>" class="ml-4 bg-blue-500 hover:bg-blue-600 text-white font-bold py-1 px-3 rounded text-sm">
                            <i class="fas fa-plus mr-1"></i> Add Record
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (count($health_records) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
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
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <a href="../health/view_record.php?id=<?php echo $record['id']; ?>" class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-gray-600">No health records found for this flock.</p>
            <?php endif; ?>
        </div>
        
        <!-- Vaccination Records -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800">Vaccination Records</h2>
                <div>
                    <a href="../vaccination/index.php?flock_id=<?php echo $flock_id; ?>" class="text-blue-600 hover:text-blue-800">
                        View All
                    </a>
                    <?php if ($flock['status'] == 'active'): ?>
                        <a href="../vaccination/record.php?flock_id=<?php echo $flock_id; ?>" class="ml-4 bg-blue-500 hover:bg-blue-600 text-white font-bold py-1 px-3 rounded text-sm">
                            <i class="fas fa-plus mr-1"></i> Add Vaccination
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (count($vaccination_records) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vaccine</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Administered By</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($vaccination_records as $record): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo format_date($record['vaccination_date']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($record['vaccine_name']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($record['administered_by_name']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <a href="../vaccination/view.php?id=<?php echo $record['id']; ?>" class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-gray-600">No vaccination records found for this flock.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Feed Consumption and Financial Summary -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Feed Consumption -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Feed Consumption</h2>
            
            <?php
            // Get recent feed consumption
            $recent_feed = db_query($pdo, "
                SELECT fc.*, f.name as feed_name
                FROM feed_consumption fc
                LEFT JOIN feeds f ON fc.feed_id = f.id
                WHERE fc.flock_id = ?
                ORDER BY fc.consumption_date DESC
                LIMIT 5
            ", [$flock_id]);
            ?>
            
            <?php if (count($recent_feed) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Feed</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity (kg)</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($recent_feed as $feed): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo format_date($feed['consumption_date']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($feed['feed_name'] ?? 'Unknown Feed'); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <div class="text-sm text-gray-900"><?php echo number_format($feed['quantity'], 2); ?></div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-4 text-right">
                    <a href="../feed/consumption.php?flock_id=<?php echo $flock_id; ?>" class="text-blue-600 hover:text-blue-800">
                        View All Feed Records <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            <?php else: ?>
                <p class="text-gray-600">No feed consumption records found for this flock.</p>
            <?php endif; ?>
        </div>
        
        <!-- Financial Summary -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Financial Summary</h2>
            
            <?php
            // Get financial summary
            $financial_summary = db_query_row($pdo, "
                SELECT 
                    SUM(CASE WHEN transaction_type = 'expense' THEN amount ELSE 0 END) as total_expenses,
                    SUM(CASE WHEN transaction_type = 'income' THEN amount ELSE 0 END) as total_income
                FROM financial_transactions
                WHERE description LIKE ?
            ", ["%Flock #$flock_id%"]);
            
            $total_expenses = $financial_summary['total_expenses'] ?? 0;
            $total_income = $financial_summary['total_income'] ?? 0;
            $net_profit = $total_income - $total_expenses;
            ?>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Total Expenses</h3>
                    <p class="text-xl font-bold text-red-600"><?php echo format_currency($total_expenses); ?></p>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Total Income</h3>
                    <p class="text-xl font-bold text-green-600"><?php echo format_currency($total_income); ?></p>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Net Profit/Loss</h3>
                    <p class="text-xl font-bold <?php echo ($net_profit >= 0) ? 'text-green-600' : 'text-red-600'; ?>">
                        <?php echo format_currency($net_profit); ?>
                    </p>
                </div>
            </div>
            
            <div class="text-right">
                <a href="../finance/transactions.php?search=Flock #<?php echo $flock_id; ?>" class="text-blue-600 hover:text-blue-800">
                    View All Transactions <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include '../../includes/footer.php';
?>

