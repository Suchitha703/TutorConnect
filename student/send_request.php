<?php
require_once '../includes/auth.php';
require_once '../includes/data.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || $auth->getUserType() !== 'student') {
    header('Location: ../login.php');
    exit();
}

$db = new Database();
$studentId = $_SESSION['user_id'];
$tutorId = $_GET['tutor_id'] ?? null;

if (!$tutorId) {
    header('Location: dashboard.php');
    exit();
}

// Check if tutor exists
$stmt = $db->conn->prepare("SELECT * FROM users WHERE user_id = ? AND user_type = 'tutor'");
$stmt->execute([$tutorId]);
$tutor = $stmt->fetch();

if (!$tutor) {
    header('Location: dashboard.php');
    exit();
}

// Only block if there is a pending or accepted request
$stmt = $db->conn->prepare("SELECT * FROM requests WHERE student_id = ? AND tutor_id = ? AND status IN ('pending', 'accepted')");
$stmt->execute([$studentId, $tutorId]);
$existingRequest = $stmt->fetch();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$existingRequest) {
    $message = $_POST['message'] ?? '';
    $subjects = implode(',', $_POST['subjects'] ?? []);
    $proposedTime = $_POST['proposed_time'] ?? null;

    try {
        $stmt = $db->conn->prepare("INSERT INTO requests (student_id, tutor_id, message, requested_subjects, proposed_time, status, created_at) 
                                   VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
        $stmt->execute([$studentId, $tutorId, $message, $subjects, $proposedTime]);

        $success = "Request sent successfully!";
        header("Refresh: 2; url=dashboard.php");
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get tutor's subjects_taught
$tutorSubjects = $tutor && isset($tutor['user_id'])
    ? array_filter(array_map('trim', explode(',', $db->conn->query("SELECT subjects_taught FROM tutors WHERE user_id = {$tutor['user_id']}")->fetchColumn())))
    : [];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Request | LocalTutorConnect</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
            <div class="p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Send Request to <?= htmlspecialchars($tutor['full_name']) ?></h2>
                <p class="text-gray-600 mb-6">Request this tutor for your learning needs</p>

                <?php if ($success): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        <?= $success ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?= $error ?>
                    </div>
                <?php endif; ?>

                <?php if ($existingRequest): ?>
                    <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-4">
                        You already have a pending or accepted request with this tutor.
                    </div>
                    <a href="dashboard.php" class="block text-center bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">
                        Back to Dashboard
                    </a>
                <?php else: ?>
                    <form method="POST">
                        <div class="mb-4">
                            <label class="block text-gray-700 mb-2">Subjects Needed</label>
                            <div class="space-y-2">
                                <?php foreach ($tutorSubjects as $subject): ?>
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" name="subjects[]" value="<?= htmlspecialchars($subject) ?>" checked class="form-checkbox text-blue-600">
                                        <span class="ml-2"><?= htmlspecialchars($subject) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 mb-2">Proposed Time</label>
                            <input type="datetime-local" name="proposed_time" class="w-full px-3 py-2 border rounded-md">
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 mb-2">Message (Optional)</label>
                            <textarea name="message" rows="3" class="w-full px-3 py-2 border rounded-md" placeholder="Tell the tutor about your learning goals..."></textarea>
                        </div>

                        <div class="flex space-x-3">
                            <a href="dashboard.php" class="flex-1 bg-gray-200 text-gray-800 py-2 px-4 rounded-md text-center hover:bg-gray-300">
                                Cancel
                            </a>
                            <button type="submit" class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">
                                Send Request
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>