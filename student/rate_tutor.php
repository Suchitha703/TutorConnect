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
$requestId = $_GET['request_id'] ?? null;

if (!$requestId) {
    header('Location: dashboard.php');
    exit();
}

// Get request details
$stmt = $db->conn->prepare("SELECT r.*, u.full_name FROM requests r JOIN users u ON r.tutor_id = u.user_id WHERE r.request_id = ? AND r.student_id = ?");
$stmt->execute([$requestId, $studentId]);
$request = $stmt->fetch();

if (!$request || $request['status'] !== 'completed') {
    header('Location: dashboard.php');
    exit();
}

// Check if already rated
$stmt = $db->conn->prepare("SELECT * FROM ratings WHERE request_id = ?");
$stmt->execute([$requestId]);
$existingRating = $stmt->fetch();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = $_POST['rating'] ?? 0;
    $review = $_POST['review'] ?? '';

    try {
        if ($existingRating) {
            throw new Exception("You have already rated this tutor for this session");
        }

        if ($rating < 1 || $rating > 5) {
            throw new Exception("Please select a valid rating");
        }

        $stmt = $db->conn->prepare("INSERT INTO ratings (student_id, tutor_id, request_id, rating_value, review_text) 
                                   VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$studentId, $request['tutor_id'], $requestId, $rating, $review]);

        $success = "Thank you for your feedback!";
        header("Refresh: 2; url=dashboard.php");
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Tutor | LocalTutorConnect</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
            <div class="p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Rate Your Tutor</h2>
                <p class="text-gray-600 mb-6">How was your session with <?= htmlspecialchars($request['full_name']) ?>?</p>

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

                <?php if ($existingRating): ?>
                    <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-4">
                        You have already rated this tutor for this session.
                    </div>
                    <a href="dashboard.php" class="block text-center bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">
                        Back to Dashboard
                    </a>
                <?php else: ?>
                    <form method="POST">
                        <div class="mb-6">
                            <label class="block text-gray-700 mb-2">Your Rating</label>
                            <div class="flex justify-center space-x-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <label class="cursor-pointer">
                                        <input type="radio" name="rating" value="<?= $i ?>" class="hidden peer" <?= $i == 5 ? 'checked' : '' ?>>
                                        <svg class="w-8 h-8 peer-checked:text-yellow-500 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                        </svg>
                                    </label>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 mb-2">Your Review (Optional)</label>
                            <textarea name="review" rows="3" class="w-full px-3 py-2 border rounded-md" placeholder="Share your experience..."></textarea>
                        </div>

                        <div class="flex space-x-3">
                            <a href="dashboard.php" class="flex-1 bg-gray-200 text-gray-800 py-2 px-4 rounded-md text-center hover:bg-gray-300">
                                Cancel
                            </a>
                            <button type="submit" class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">
                                Submit Rating
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>