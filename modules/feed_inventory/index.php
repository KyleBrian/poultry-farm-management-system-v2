<?php
$page_title = "Feed Inventory";
require_once '../../includes/header.php';
require_once '../../includes/functions.php';

// Fetch feed inventory records
$stmt = $pdo->query("SELECT * FROM feed_inventory ORDER BY purchase_date DESC");
$feed_inventory = $stmt->fetchAll();

?>

<div class="mb-4 flex justify-between items-center">
    <h1 class="text-2xl font-bold">Feed Inventory</h1>
    <a href="add.php" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
        <i class="fas fa-plus mr-2"></i>Add New Feed
    </a>
</div>

<div class="bg-white shadow-md rounded my-6">
    <table class="min-w-max w-full table-auto">
        <thead>
            <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                <th class="py-3 px-6 text-left">Feed Type</th>
                <th class="py-3 px-6 text-center">Quantity (kg)</th>
                <th class="py-3 px-6 text-center">Purchase Date</th>
                <th class="py-3 px-6 text-center">Expiry Date</th>
                <th class="py-3 px-6 text-center">Actions</th>
            </tr>
        </thead>
        <tbody class="text-gray-600 text-sm font-light">
            <?php foreach ($feed_inventory as $feed): ?>
            <tr class="border-b border-gray-200 hover:bg-gray-100">
                <td class="py-3 px-6 text-left whitespace-nowrap">
                    <div class="flex items-center">
                        <span class="font-medium"><?php echo htmlspecialchars($feed['feed_type']); ?></span>
                    </div>
                </td>
                <td class="py-3 px-6 text-center">
                    <span><?php echo number_format($feed['quantity_kg'], 2); ?></span>
                </td>
                <td class="py-3 px-6 text-center">
                    <span><?php echo formatDate($feed['purchase_date']); ?></span>
                </td>
                <td class="py-3 px-6 text-center">
                    <span><?php echo formatDate($feed['expiry_date']); ?></span>
                </td>
                <td class="py-3 px-6 text-center">
                    <div class="flex item-center justify-center">
                        <a href="view.php?id=<?php echo $feed['id']; ?>" class="w-4 mr-2 transform hover:text-purple-500 hover:scale-110">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="edit.php?id=<?php echo $feed['id']; ?>" class="w-4 mr-2 transform hover:text-purple-500 hover:scale-110">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="delete.php?id=<?php echo $feed['id']; ?>" class="w-4 mr-2 transform hover:text-red-500 hover:scale-110" onclick="return confirm('Are you sure you want to delete this feed record?');">
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

