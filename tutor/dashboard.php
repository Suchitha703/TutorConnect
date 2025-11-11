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

// Get tutor info
$stmt = $db->conn->prepare("SELECT * FROM tutors WHERE user_id = ?");
$stmt->execute([$tutorId]);
$tutor = $stmt->fetch();

// Get pending requests
$stmt = $db->conn->prepare("SELECT r.*, u.full_name, s.grade FROM requests r 
                           JOIN users u ON r.student_id = u.user_id 
                           JOIN students s ON r.student_id = s.user_id
                           WHERE r.tutor_id = ? AND r.status = 'pending'");
$stmt->execute([$tutorId]);
$pendingRequests = $stmt->fetchAll();

// Get accepted requests
$stmt = $db->conn->prepare("SELECT r.*, u.full_name, s.grade FROM requests r 
                           JOIN users u ON r.student_id = u.user_id 
                           JOIN students s ON r.student_id = s.user_id
                           WHERE r.tutor_id = ? AND r.status = 'accepted'");
$stmt->execute([$tutorId]);
$acceptedRequests = $stmt->fetchAll();

// Get recent messages
$stmt = $db->conn->prepare("SELECT m.*, u.full_name FROM messages m
                           JOIN users u ON m.sender_id = u.user_id
                           WHERE m.receiver_id = ? AND m.is_read = FALSE
                           ORDER BY m.created_at DESC");
$stmt->execute([$tutorId]);
$unreadMessages = $stmt->fetchAll();

// Get completed requests for this tutor
$stmt = $db->conn->prepare("SELECT r.*, u.full_name, s.grade FROM requests r
    JOIN users u ON r.student_id = u.user_id
    JOIN students s ON r.student_id = s.user_id
    WHERE r.tutor_id = ? AND r.status = 'completed'
    ORDER BY r.created_at DESC");
$stmt->execute([$tutorId]);
$completedRequests = $stmt->fetchAll();

// Calculate overall rating for this tutor
$stmt = $db->conn->prepare("SELECT AVG(rating_value) as avg_rating, COUNT(*) as total_ratings FROM ratings WHERE tutor_id = ?");
$stmt->execute([$tutorId]);
$ratingStats = $stmt->fetch();
$overallRating = $ratingStats['avg_rating'] ? number_format($ratingStats['avg_rating'], 1) : 'N/A';
$totalRatings = $ratingStats['total_ratings'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutor Dashboard | LocalTutorConnect</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8 flex justify-between items-center">
            <h1 class="text-xl font-bold text-gray-900">Tutor Dashboard</h1>
            <div class="flex items-center space-x-4">
                <a href="../logout.php" class="text-gray-600 hover:text-gray-900">Logout</a>
                <a href="./edit_profile.php"><img src="../assets/images/tutor.jpg" alt="Profile" class="h-8 w-8 rounded-full"></a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
        <!-- Welcome Banner -->
        <div class="bg-blue-600 text-white rounded-lg p-6 mb-6">
            <h2 class="text-2xl font-bold mb-2">Welcome, <?= $_SESSION['name'] ?? 'Tutor' ?></h2>
            <p>You have <?= count($pendingRequests) ?> pending requests and <?= count($acceptedRequests) ?> active students</p>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-gray-500">Pending Requests</h3>
                        <p class="text-2xl font-bold"><?= count($pendingRequests) ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-gray-500">Active Students</h3>
                        <p class="text-2xl font-bold"><?= count($acceptedRequests) ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-gray-500">Unread Messages</h3>
                        <p class="text-2xl font-bold"><?= count($unreadMessages) ?></p>
                    </div>
                </div>
            </div>
            <!-- Overall Rating -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-gray-500">Overall Rating</h3>
                        <p class="text-2xl font-bold">
                            <?= $overallRating ?>
                            <?php if ($overallRating !== 'N/A'): ?>
                                <span class="text-yellow-500">★</span>
                            <?php endif; ?>
                        </p>
                        <p class="text-sm text-gray-400"><?= $totalRatings ?> review<?= $totalRatings == 1 ? '' : 's' ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Requests -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Pending Requests</h3>
                <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm"><?= count($pendingRequests) ?> New</span>
            </div>

            <?php if (empty($pendingRequests)): ?>
                <p class="text-gray-600">You have no pending requests at this time.</p>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($pendingRequests as $request): ?>
                    <div class="border rounded-lg p-4">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="font-bold"><?= htmlspecialchars($request['full_name']) ?></h4>
                                <p class="text-sm text-gray-600">Grade <?= htmlspecialchars($request['grade']) ?></p>
                                <p class="mt-2 text-sm">Subjects: <?= htmlspecialchars($request['requested_subjects']) ?></p>
                                <?php if ($request['proposed_time']): ?>
                                    <p class="text-sm">Proposed Time: <?= date('M j, Y g:i A', strtotime($request['proposed_time'])) ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="flex space-x-2">
                                <a href="respond_request.php?request_id=<?= $request['request_id'] ?>&action=accept" class="bg-green-500 text-white px-3 py-1 rounded-md text-sm hover:bg-green-600">Accept</a>
                                <a href="respond_request.php?request_id=<?= $request['request_id'] ?>&action=reject" class="bg-red-500 text-white px-3 py-1 rounded-md text-sm hover:bg-red-600">Reject</a>
                                <a href="chat.php?student_id=<?= $request['student_id'] ?>" class="bg-blue-500 text-white px-3 py-1 rounded-md text-sm hover:bg-blue-600">Message</a>
                            </div>
                        </div>
                        <?php if (!empty($request['message'])): ?>
                            <div class="mt-3 p-3 bg-gray-50 rounded-md">
                                <p class="text-sm text-gray-700"><?= htmlspecialchars($request['message']) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Active Students -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Your Students</h3>
                <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm"><?= count($acceptedRequests) ?> Active</span>
            </div>

            <?php if (empty($acceptedRequests)): ?>
                <p class="text-gray-600">You have no active students at this time.</p>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($acceptedRequests as $request): ?>
                    <div class="border rounded-lg p-4">
                        <div class="flex items-center mb-3">
                            <img src="../assets/images/student.jpg" alt="Student" class="h-10 w-10 rounded-full mr-3">
                            <div>
                                <h4 class="font-bold"><?= htmlspecialchars($request['full_name']) ?></h4>
                                <p class="text-sm text-gray-600">Grade <?= htmlspecialchars($request['grade']) ?></p>
                            </div>
                        </div>
                        <div class="space-y-2 text-sm">
                            <p>Subjects: <?= htmlspecialchars($request['requested_subjects']) ?></p>
                            <p>Since: <?= date('M j, Y', strtotime($request['created_at'])) ?></p>
                        </div>
                        <div class="mt-4 flex space-x-2">
                            <a href="chat.php?student_id=<?= $request['student_id'] ?>" class="flex-1 bg-blue-500 text-white py-1 px-3 rounded-md text-center text-sm hover:bg-blue-600">Message</a>
                            <a href="view_reviews.php?student_id=<?= $request['student_id'] ?>" class="flex-1 bg-gray-200 text-gray-800 py-1 px-3 rounded-md text-center text-sm hover:bg-gray-300">Review</a>
                            
                        </div>
                        <div>
                            <?php
                                // Use proposed_time or your appointment time field
                                $appointmentTime = isset($request['proposed_time']) ? strtotime($request['proposed_time']) : null;
                                $now = time();
                            ?>
                            <?php if ($appointmentTime && $now >= $appointmentTime): ?>
                                <form method="post" action="mark_completed.php" style="display:inline;">
                                    <input type="hidden" name="request_id" value="<?= $request['request_id'] ?>">
                                    <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded">Mark as Completed</button>
                                </form>
                            <?php else: ?>
                                <span class="text-gray-400 text-sm">You can mark as completed after the session time.</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-lg shadow p-6 mt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Completed Sessions</h3>
            <?php if (empty($completedRequests)): ?>
                <p class="text-gray-600">No completed sessions yet.</p>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($completedRequests as $request): ?>
                        <?php
                        // Fetch rating for this request
                        $stmt2 = $db->conn->prepare("SELECT rating_value, review_text FROM ratings WHERE request_id = ?");
                        $stmt2->execute([$request['request_id']]);
                        $rating = $stmt2->fetch();
                        ?>
                        <div class="border rounded-lg p-4 flex justify-between items-center">
                            <div>
                                <h4 class="font-bold"><?= htmlspecialchars($request['full_name']) ?></h4>
                                <p class="text-sm text-gray-600">Grade <?= htmlspecialchars($request['grade']) ?></p>
                                <p class="text-sm">Subjects: <?= htmlspecialchars($request['requested_subjects']) ?></p>
                                <p class="text-sm">Completed on <?= date('M d, Y', strtotime($request['created_at'])) ?></p>                            
                                <?php if ($rating): ?>
                                    <div class="mt-2">
                                        <span class="font-semibold text-yellow-600">Rating: <?= number_format($rating['rating_value'], 1) ?> / 5</span>
                                        <?php if (!empty($rating['review'])): ?>
                                            <p class="text-gray-700 italic mt-1">"<?= htmlspecialchars($rating['review']) ?>"</p>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-gray-400">No rating yet</span>
                                <?php endif; ?>
                            </div>
                            <a href="chat.php?student_id=<?= $request['student_id'] ?>" class="bg-blue-500 text-white px-3 py-1 rounded-md text-sm hover:bg-blue-600">Message</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>