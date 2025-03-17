<?php
$page_title = "Health Records";
require_once '../../includes/header.php';
require_once '../../includes/functions.php';

// Fetch health records
$stmt = $pdo->query("
    SELECT hr.*, f.name as flock_name 
    FROM health_records hr 
    JOIN flocks f ON hr.flock_id = f.id 
    ORDER BY hr.record_date DESC
");
$health_records = $stmt->fetchAll();

?>

<div class="mb-4 flex justify-between items-center">
    <h1 class="text-2xl font-bold">Health Records</h1>
    <a href="add.php" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
        <i class="fas fa-plus mr-2"></i>Add New Record
    </a>
</div>

<div class="bg-white shadow-md rounded my-6">
    <table class="min-w-max w-full table-auto">
        <thead>
            <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                <th class="py-3 px-6 text-left">Date</th>
                <th class="py-3 px-6 text-left">Flock</th>
                <th class="py-3 px-6 text-left">Issue</th>
                <th class="py-3 px-6 text-center">Treatment</th>
                <th class="py-3 px-6 text-center">Actions</th>
            </tr>
        </thead>
        <tbody class="text-gray-600 text-sm font-light">
            <?php foreach ($health_records as $record): ?>
            <tr class="border-b border-gray-200 hover:bg-gray-100">
                <td class="py-3 px-6 text-left whitespace-nowrap">
                    <span class="font-medium"><?php echo formatDate($record['record_date']); ?></span>
                </td>
                <td class="py-3 px-6 text-left">
                    <span><?php echo htmlspecialchars($record['flock_name']); ?></span>
                </td>
                <td class="py-3 px-6 text-left">
                    <span><?php echo htmlspecialchars($record['issue']); ?></span>
                </td>
                <td class="py-3 px-6 text-center">
                    <span><?php echo htmlspecialchars($record['treatment']); ?></span>
                </td>
                <td class="py-3 px-6 text-center">
                    <div class="flex item-center justify-center">
                        <a href="view.php?id=<?php echo $record['id']; ?>" class="w-4 mr-2 transform hover:text-purple-500 hover:scale-110">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="edit.php?id=<?php echo $record['id']; ?>" class="w-4 mr-2 transform hover:text-purple-500 hover:scale-110">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="delete.php?id=<?php echo $record['id']; ?>" class="w-4 mr-2 transform hover:text-red-500 hover:scale-110" onclick="return confirm('Are you sure you want to delete this health record?');">
                            <i class="fas fa-trash-alt"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once '../../includes/footer.php'; ?>

