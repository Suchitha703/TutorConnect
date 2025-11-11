<?php
require_once '../includes/auth.php';
require_once '../includes/data.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || $auth->getUserType() !== 'tutor') {
    header('Location: ../login.php');
    exit();
}

$db = new Database();
$tutorId = $_SESSION['user_id'];
$requestId = $_GET['request_id'] ?? null;
$action = $_GET['action'] ?? null;

if (!$requestId || !in_array($action, ['accept', 'reject'])) {
    header('Location: dashboard.php');
    exit();
}

// Verify request belongs to this tutor
$stmt = $db->conn->prepare("SELECT * FROM requests WHERE request_id = ? AND tutor_id = ?");
$stmt->execute([$requestId, $tutorId]);
$request = $stmt->fetch();

if (!$request) {
    header('Location: dashboard.php');
    exit();
}

// Update request status
$newStatus = $action === 'accept' ? 'accepted' : 'rejected';
$stmt = $db->conn->prepare("UPDATE requests SET status = ? WHERE request_id = ?");
$stmt->execute([$newStatus, $requestId]);

// Send notification to student
$message = $action === 'accept' 
    ? "Your request has been accepted by the tutor!" 
    : "Your request has been declined by the tutor.";

$stmt = $db->conn->prepare("INSERT INTO messages (sender_id, receiver_id, request_id, message_text) 
                           VALUES (?, ?, ?, ?)");
$stmt->execute([$tutorId, $request['student_id'], $requestId, $message]);

header('Location: dashboard.php');
exit();
?>