<?php
require_once '../../config/config.php';
check_login();

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = intval($_GET['id']);

$stmt = $pdo->prepare("DELETE FROM flocks WHERE id = ?");
if ($stmt->execute([$id])) {
    $_SESSION['success_msg'] = "Flock deleted successfully.";
} else {
    $_SESSION['error_msg'] = "Error deleting flock. Please try again.";
}

header("Location: index.php");
exit();

