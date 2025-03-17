<?php
/**
 * File: modules/health/view_record.php
 * View health record details
 * @version 1.0.2
 * @integration_verification PMSFV-031
 */
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/functions.php';

// Check if user is logged in
check_login();

// Check if record ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    set_flash_message('error', 'Invalid health record ID.');
    header("Location: index.php");
    exit();
}

$record_id = intval($_GET['id']);

// Get health record details
$record = db_query_row($pdo, "
    SELECT hr.*, f.name as flock_name, f.breed as flock_breed, u.username as recorded_by_name
    FROM health_records hr
    JOIN flocks f ON hr.flock_id = f.id
    JOIN users u ON hr.recorded_by = u.id
    WHERE hr.id = ?
", [$record_id]);

if (!$record) {
    set_flash_message('error', 'Health record not found.');
    header("Location: index.php");
    exit();
}

// Page title
$page_title = "View Health Record";

// Include header
include '../../includes/header.php';
?>

<!-- View Health Record -->
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Health Record Details</h1>
        <div>
            <?php if (has_permission('manage_health')): ?>
                <a href="edit_record.php?id=<?php echo $record_id; ?>" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded mr-2">
                    <i class="fas fa-edit mr-2"></i> Edit Record
                </a>
            <?php endif; ?>
            <a href="index.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-arrow-left mr-2"></i> Back to Health Records
            </a>
        </div>
    </div>
    
    <?php display_flash_message(); ?>
    
    <!-- Record Details -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Record Information</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div>
                <h3 class="text-sm font-medium text-gray-500">Flock</h3>
                <p class="mt-1 text-lg text-gray-900"><?php echo htmlspecialchars($record['flock_name']); ?> (<?php echo htmlspecialchars($record['flock_breed']); ?>)</p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Record Date</h3>
                <p class="mt-1 text-lg text-gray-900"><?php echo format_date($record['record_date']); ?></p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Record Type</h3>
                <p class="mt-1">
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
                </p>
            </div>
            
            <?php if (!empty($record['recovery_status'])): ?>
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Recovery Status</h3>
                    <p class="mt-1">
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
                        }
                        ?>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-<?php echo $status_color; ?>-100 text-<?php echo $status_color; ?>-800">
                            <?php echo ucfirst($record['recovery_status']); ?>
                        </span>
                    </p>
                </div>
            <?php endif; ?>
            
            <div>
                <h3 class="text-sm font-medium text-gray-500">Recorded By</h3>
                <p class="mt-1 text-lg text-gray-900"><?php echo htmlspecialchars($record['recorded_by_name']); ?></p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500">Created At</h3>
                <p class="mt-1 text-lg text-gray-900"><?php echo format_datetime($record['created_at']); ?></p>
            </div>
        </div>
        
        <?php if (!empty($record['symptoms'])): ?>
            <div class="mt-6">
                <h3 class="text-sm font-medium text-gray-500">Symptoms</h3>
                <div class="mt-1 p-4 bg-gray-50 rounded-md">
                    <p class="text-gray-900"><?php echo nl2br(htmlspecialchars($record['symptoms'])); ?></p>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($record['diagnosis'])): ?>
            <div class="mt-6">
                <h3 class="text-sm font-medium text-gray-500">Diagnosis</h3>
                <div class="mt-1 p-4 bg-gray-50 rounded-md">
                    <p class="text-gray-900"><?php echo nl2br(htmlspecialchars($record['diagnosis'])); ?></p>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($record['treatment'])): ?>
            <div class="mt-6">
                <h3 class="text-sm font-medium text-gray-500">Treatment</h3>
                <div class="mt-1 p-4 bg-gray-50 rounded-md">
                    <p class="text-gray-900"><?php echo nl2br(htmlspecialchars($record['treatment'])); ?></p>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($record['medication'])): ?>
            <div class="mt-6">
                <h3 class="text-sm font-medium text-gray-500">Medication</h3>
                <div class="mt-1 p-4 bg-gray-50 rounded-md">
                    <p class="text-gray-900"><?php echo nl2br(htmlspecialchars($record['medication'])); ?></p>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($record['notes'])): ?>
            <div class="mt-6">
                <h3 class="text-sm font-medium text-gray-500">Notes</h3>
                <div class="mt-1 p-4 bg-gray-50 rounded-md">
                    <p class="text-gray-900"><?php echo nl2br(htmlspecialchars($record['notes'])); ?></p>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Related Records -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Related Health Records</h2>
        
        <?php
        // Get related health records for the same flock
        $related_records = db_query($pdo, "
            SELECT hr.*, u.username as recorded_by_name
            FROM health_records hr
            JOIN users u ON hr.recorded_by = u.id
            WHERE hr.flock_id = ? AND hr.id != ?
            ORDER BY hr.record_date DESC
            LIMIT 5
        ", [$record['flock_id'], $record_id]);
        ?>
        
        <?php if (count($related_records) > 0): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recorded By</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($related_records as $related): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo format_date($related['record_date']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $type_color = '';
                                    switch ($related['record_type']) {
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
                                        <?php echo ucfirst(str_replace('_', ' ', $related['record_type'])); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $status_color = '';
                                    switch ($related['recovery_status']) {
                                        case 'ongoing':
                                            $status_color = 'yellow';
                                            break;
                                        case 'recovered':
                                            $status_color = 'green';
                                            break;
                                        case 'deceased':
                                            $status_color = 'red';
                                            break;
                                    }
                                    ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-<?php echo $status_color; ?>-100 text-<?php echo $status_color; ?>-800">
                                        <?php echo ucfirst($related['recovery_status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($related['recorded_by_name']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="view_record.php?id=<?php echo $related['id']; ?>" class="text-blue-600 hover:text-blue-900">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-4 text-right">
                <a href="index.php?flock_id=<?php echo $record['flock_id']; ?>" class="text-blue-500 hover:text-blue-700">
                    View All Health Records for this Flock <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        <?php else: ?>
            <p class="text-gray-600">No other health records found for this flock.</p>
        <?php endif; ?>
    </div>
</div>

<?php
// Include footer
include '../../includes/footer.php';
?>

