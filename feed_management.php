<?php
require_once 'config/config.php';
check_login();

// Handle form submission for adding new feed inventory
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_feed'])) {
    $feed_type = sanitize_input($_POST['feed_type']);
    $quantity_kg = floatval($_POST['quantity_kg']);
    $price_per_kg = floatval($_POST['price_per_kg']);
    $purchase_date = sanitize_input($_POST['purchase_date']);
    $expiry_date = sanitize_input($_POST['expiry_date']);

    $stmt = $pdo->prepare("INSERT INTO feed_inventory (feed_type, quantity_kg, price_per_kg, purchase_date, expiry_date) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([$feed_type, $quantity_kg, $price_per_kg, $purchase_date, $expiry_date])) {
        $_SESSION['success_msg'] = "New feed inventory added successfully.";
    } else {
        $_SESSION['error_msg'] = "Error adding new feed inventory.";
    }
    header("Location: feed_management.php");
    exit();
}

// Fetch feed inventory
$stmt = $pdo->query("SELECT * FROM feed_inventory ORDER BY purchase_date DESC");
$feed_inventory = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feed Management - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8">Feed Management</h1>
        
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
        
        <div class="bg-white p-6 rounded-lg shadow mb-8">
            <h2 class="text-xl font-semibold mb-4">Add New Feed Inventory</h2>
            <form method="POST" action="">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="feed_type">
                            Feed Type
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="feed_type" type="text" name="feed_type" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="quantity_kg">
                            Quantity (kg)
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="quantity_kg" type="number" step="0.01" name="quantity_kg" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="price_per_kg">
                            Price per kg
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="price_per_kg" type="number" step="0.01" name="price_per_kg" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="purchase_date">
                            Purchase Date
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="purchase_date" type="date" name="purchase_date" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="expiry_date">
                            Expiry Date
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="expiry_date" type="date" name="expiry_date" required>
                    </div>
                </div>
                <div class="mt-4">
                    <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit" name="add_feed">
                        Add Feed Inventory
                    </button>
                </div>
            </form>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-xl font-semibold mb-4">Feed Inventory</h2>
            <table class="w-full">
                <thead>
                    <tr>
                        <th class="text-left py-2">Feed Type</th>
                        <th class="text-left py-2">Quantity (kg)</th>
                        <th class="text-left py-2">Price per kg</th>
                        <th class="text-left py-2">Purchase Date</th>
                        <th class="text-left py-2">Expiry Date</th>
                        <th class="text-left py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($feed_inventory as $feed): ?>
                        <tr>
                            <td class="py-2"><?php echo htmlspecialchars($feed['feed_type']); ?></td>
                            <td class="py-2"><?php echo number_format($feed['quantity_kg'], 2); ?></td>
                            <td class="py-2"><?php echo format_currency($feed['price_per_kg']); ?></td>
                            <td class="py-2"><?php echo format_date($feed['purchase_date']); ?></td>
                            <td class="py-2"><?php echo format_date($feed['expiry_date']); ?></td>
                            <td class="py-2">
                                <a href="edit_feed.php?id=<?php echo $feed['id']; ?>" class="text-blue-500 hover:text-blue-700">Edit</a>
                                <a href="delete_feed.php?id=<?php echo $feed['id']; ?>" class="text-red-500 hover:text-red-700 ml-2" onclick="return confirm('Are you sure you want to delete this feed inventory?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

