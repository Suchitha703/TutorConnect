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
$studentId = $_GET['student_id'] ?? null;

// Get all ratings for this tutor
$query = "SELECT r.*, u.full_name FROM ratings r 
          JOIN users u ON r.student_id = u.user_id
          WHERE r.tutor_id = ?";
$params = [$tutorId];

if ($studentId) {
    $query .= " AND r.student_id = ?";
    $params[] = $studentId;
}

$query .= " ORDER BY r.created_at DESC";

$stmt = $db->conn->prepare($query);
$stmt->execute($params);
$ratings = $stmt->fetchAll();

// Calculate average rating
$stmt = $db->conn->prepare("SELECT AVG(rating_value) as avg_rating, COUNT(*) as total_ratings FROM ratings WHERE tutor_id = ?");
$stmt->execute([$tutorId]);
$stats = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reviews | LocalTutorConnect</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">My Reviews</h1>
            <a href="dashboard.php" class="text-blue-600 hover:underline">Back to Dashboard</a>
        </div>

        <!-- Stats -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex items-center justify-center space-x-12">
                <div class="text-center">
                    <p class="text-5xl font-bold text-gray-900"><?= number_format($stats['avg_rating'] ?? 0, 1) ?></p>
                    <p class="text-gray-600">Average Rating</p>
                </div>
                <div class="text-center">
                    <p class="text-5xl font-bold text-gray-900"><?= $stats['total_ratings'] ?></p>
                    <p class="text-gray-600">Total Reviews</p>
                </div>
            </div>
        </div>

        <!-- Reviews -->
        <div class="bg-white rounded-lg shadow p-6">
            <?php if (empty($ratings)): ?>
                <p class="text-gray-600 text-center py-8">You have no reviews yet.</p>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($ratings as $rating): ?>
                    <div class="border-b pb-6 last:border-b-0 last:pb-0">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <h3 class="font-bold"><?= htmlspecialchars($rating['full_name']) ?></h3>
                                <div class="flex items-center mt-1">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <svg class="w-5 h-5 <?= $i <= $rating['rating_value'] ? 'text-yellow-400' : 'text-gray-300' ?>" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                        </svg>
                                    <?php endfor; ?>
                                    <span class="ml-2 text-gray-600 text-sm"><?= date('M j, Y', strtotime($rating['created_at'])) ?></span>
                                </div>
                            </div>
                        </div>
                        <?php if (!empty($rating['review_text'])): ?>
                            <p class="text-gray-700 mt-2"><?= htmlspecialchars($rating['review_text']) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>