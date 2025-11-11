<?php

require_once '../includes/auth.php';
require_once '../includes/data.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || $auth->getUserType() !== 'tutor') {
    header('Location: ../login.php');
    exit();
}

$db = new Database();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['request_id'])) {
    $requestId = (int)$_POST['request_id'];
    // Optionally, check if this tutor owns the request
    $stmt = $db->conn->prepare("UPDATE requests SET status = 'completed' WHERE request_id = ?");
    $stmt->execute([$requestId]);
    header('Location: dashboard.php?completed=1');
    exit();
}
header('Location: dashboard.php');
exit();
?>