<?php
$page_title = "Add Vaccination";
require_once '../../includes/header.php';
require_once '../../includes/functions.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Insert vaccination record
        $stmt = $pdo->prepare("
            INSERT INTO vaccination_schedule (
                flock_id, vaccine_id, scheduled_date, 
                dosage, notes, status
            ) VALUES (?, ?, ?, ?, ?, 'scheduled')
        ");
        
        $stmt->execute([
            $_POST['flock_id'],
            $_POST['vaccine_id'],
            $_POST['scheduled_date'],
            $_POST['dosage'],
            $_POST['notes']
        ]);
        
        $_SESSION['success_msg'] = "Vaccination scheduled successfully.";
        header("Location: index.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error_msg'] = "Error scheduling vaccination: " . $e->getMessage();
    }
}

// Fetch flocks for dropdown
$stmt = $pdo->query("SELECT id, name FROM flocks WHERE health_status != 'deceased' ORDER BY name");
$flocks = $stmt->fetchAll();

// Fetch vaccines for dropdown
$stmt = $pdo->query("SELECT id, vaccine_name FROM vaccination_types ORDER BY vaccine_name");
$vaccines = $stmt->fetchAll();
?>

<div class="mb-4">
    <h1 class="text-2xl font-bold">Schedule Vaccination</h1>
</div>

<?php
if (isset($_SESSION['error_msg'])) {
    echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>{$_SESSION['error_msg']}</div>";
    unset($_SESSION['error_msg']);
}
?>

<form method="POST" action="" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
    <div class="mb-4">
        <label class="block text-gray-700 text-sm font-bold mb-2" for="flock_id">
            Flock *
        </label>
        <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="flock_id" name="flock_id" required>
            <option value="">Select Flock</option>
            <?php foreach ($flocks as $flock): ?>
                <option value="<?php echo $flock['id']; ?>"><?php echo htmlspecialchars($flock['name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div class="mb-4">
        <label class="block text-gray-700 text-sm font-bold mb-2" for="vaccine_id">
            Vaccine *
        </label>
        <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="vaccine_id" name="vaccine_id" required>
            <option value="">Select Vaccine</option>
            <?php foreach ($vaccines as $vaccine): ?>
                <option value="<?php echo $vaccine['id']; ?>"><?php echo htmlspecialchars($vaccine['vaccine_name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div class="mb-4">
        <label class="block text-gray-700 text-sm font-bold mb-2" for="scheduled_date">
            Scheduled Date *
        </label>
        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="scheduled_date" name="scheduled_date" type="date" required>
    </div>
    
    <div class="mb-4">
        <label class="block text-gray-700 text-sm font-bold mb-2" for="dosage">
            Dosage
        </label>
        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="dosage" name="dosage" type="text" placeholder="e.g., 0.5ml per bird">
    </div>
    
    <div class="mb-6">
        <label class="block text-gray-700 text-sm font-bold mb-2" for="notes">
            Notes
        </label>
        <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="notes" name="notes" rows="3"></textarea>
    </div>
    
    <div class="flex items-center justify-between">
        <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">
            Schedule Vaccination
        </button>
        <a href="index.php" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
            Cancel
        </a>
    </div>
</form>

<?php require_once '../../includes/footer.php'; ?>

