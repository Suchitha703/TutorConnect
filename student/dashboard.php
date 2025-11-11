
<?php
require_once '../includes/auth.php';
require_once '../includes/data.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || $auth->getUserType() !== 'student') {
    header('Location: ../login.php');
    exit();
}

$db = new Database();
$userId = $_SESSION['user_id'];

// Get student info
$stmt = $db->conn->prepare("SELECT * FROM students WHERE user_id = ?");
$stmt->execute([$userId]);
$student = $stmt->fetch();

// Get matched tutors based on student's subjects and location
$searchLocation = $_GET['location'] ?? '';
$searchSubject = $_GET['subject'] ?? '';
$experience = $_GET['experience'] ?? '';
$day = $_GET['day'] ?? '';
$time = $_GET['time'] ?? '';

$query = "SELECT u.*, t.* FROM users u JOIN tutors t ON u.user_id = t.user_id WHERE 1=1";
$params = [];

if (!empty($experience)) {
    $query .= " AND t.experience_years >= ?";
    $params[] = (int)$experience;
}

if (!empty($day)) {
    $query .= " AND EXISTS (
        SELECT 1 FROM tutor_availability ta
        WHERE ta.tutor_id = t.tutor_id AND ta.day_of_week = ?
    )";
    $params[] = $day;
}

if (!empty($time)) {
    $query .= " AND EXISTS (
        SELECT 1 FROM tutor_availability ta
        WHERE ta.tutor_id = t.tutor_id
        AND ta.start_time <= ? AND ta.end_time >= ?
        " . (!empty($day) ? "AND ta.day_of_week = ?" : "") . "
    )";
    $params[] = $time;
    $params[] = $time;
    if (!empty($day)) $params[] = $day;
}

if (!empty($searchLocation)) {
    $query .= " AND (u.city LIKE ? OR u.area LIKE ?)";
    $params[] = "%$searchLocation%";
    $params[] = "%$searchLocation%";
}

// Subject filter: use all student subjects if no subject is selected
$filtersDefault = empty($searchLocation) && empty($searchSubject) && empty($experience) && empty($day) && empty($time);

if (!empty($searchSubject)) {
    // Only filter by subject if selected in the filter
    $query .= " AND FIND_IN_SET(?, REPLACE(t.subjects_taught, ' ', '')) > 0";
    $params[] = str_replace(' ', '', $searchSubject);
}

$query .= " ORDER BY t.experience_years DESC LIMIT 10";

$stmt = $db->conn->prepare($query);
$stmt->execute($params);
$tutors = $stmt->fetchAll();

$tutorIds = array_column($tutors, 'user_id');
$ratings = [];
if (!empty($tutorIds)) {
    $in  = str_repeat('?,', count($tutorIds) - 1) . '?';
    $stmt = $db->conn->prepare("SELECT t.user_id, AVG(r.rating_value) as avg_rating
        FROM tutors t
        LEFT JOIN requests req ON req.tutor_id = t.user_id
        LEFT JOIN ratings r ON r.request_id = req.request_id
        WHERE t.user_id IN ($in)
        GROUP BY t.user_id");
    $stmt->execute($tutorIds);
    foreach ($stmt->fetchAll() as $row) {
        $ratings[$row['user_id']] = $row['avg_rating'] !== null ? round($row['avg_rating'], 1) : null;
    }
}


// Get pending requests
$stmt = $db->conn->prepare("SELECT r.*, u.full_name FROM requests r JOIN users u ON r.tutor_id = u.user_id WHERE r.student_id = ? AND r.status = 'pending'");
$stmt->execute([$userId]);
$pendingRequests = $stmt->fetchAll();

// Get all accepted requests for this student
$stmt = $db->conn->prepare("SELECT tutor_id FROM requests WHERE student_id = ? AND status = 'accepted'");
$stmt->execute([$userId]);
$acceptedTutorIds = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

// Get all completed requests for this student
$stmt = $db->conn->prepare("SELECT r.*, u.full_name FROM requests r 
    JOIN users u ON r.tutor_id = u.user_id 
    WHERE r.student_id = ? AND r.status = 'completed'");
$stmt->execute([$userId]);
$completedRequests = $stmt->fetchAll();

// Get all requests for this student (pending, accepted, completed) grouped by tutor
$stmt = $db->conn->prepare("SELECT tutor_id, status FROM requests WHERE student_id = ?");
$stmt->execute([$userId]);
$allRequests = $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_COLUMN);

// Get chat history: all tutors the student has chatted with
$stmt = $db->conn->prepare("
    SELECT DISTINCT u.user_id, u.full_name
    FROM messages m
    JOIN users u ON (
        (m.sender_id = u.user_id AND m.receiver_id = ?) OR
        (m.receiver_id = u.user_id AND m.sender_id = ?)
    )
    WHERE u.user_type = 'tutor' AND (m.sender_id = ? OR m.receiver_id = ?)
    ORDER BY u.full_name
");
$stmt->execute([$userId, $userId, $userId, $userId]);
$chatTutors = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | LocalTutorConnect</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8 flex justify-between items-center">
            <h1 class="text-xl font-bold text-gray-900">Student Dashboard</h1>
            <div class="flex items-center space-x-4">
                <a href="../logout.php" class="text-gray-600 hover:text-gray-900">Logout</a>
                <a href="./edit_profile.php"><img src="../assets/images/student.jpg" alt="Profile" class="h-8 w-8 rounded-full"></a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
        <!-- Welcome Banner -->
        <div class="bg-blue-600 text-white rounded-lg p-6 mb-6">
            <h2 class="text-2xl font-bold mb-2">Welcome, <?= $_SESSION['name'] ?? 'Student' ?></h2>
            <p>Find the perfect tutor for your learning needs</p>
        </div>

        <!-- Search and Filter -->
        <div class="bg-white rounded-lg shadow p-6 mb-6 flex flex-col space-y-4">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <!-- Location Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                    <input type="text" name="location" value="<?= htmlspecialchars($searchLocation) ?>" placeholder="Your area or pincode" class="w-full px-3 py-2 border rounded-md">
                </div>
                <!-- Subject Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                    <select name="subject" class="w-full px-3 py-2 border rounded-md">
                        <option value="">All Subjects</option>
                        <?php 
                        $subjects = ['Math', 'Science', 'English', 'History', 'Computer Science'];
                        foreach ($subjects as $subject): ?>
                            <option value="<?= $subject ?>" <?= $searchSubject === $subject ? 'selected' : '' ?>>
                                <?= $subject ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Experience Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Experience (years)</label>
                    <select name="experience" class="w-full px-3 py-2 border rounded-md">
                        <option value="">Any</option>
                        <option value="1" <?= ($_GET['experience'] ?? '') === '1' ? 'selected' : '' ?>>1+</option>
                        <option value="3" <?= ($_GET['experience'] ?? '') === '3' ? 'selected' : '' ?>>3+</option>
                        <option value="5" <?= ($_GET['experience'] ?? '') === '5' ? 'selected' : '' ?>>5+</option>
                        <option value="10" <?= ($_GET['experience'] ?? '') === '10' ? 'selected' : '' ?>>10+</option>
                    </select>
                </div>
                <!-- Day Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Day</label>
                    <select name="day" class="w-full px-3 py-2 border rounded-md">
                        <option value="">Any</option>
                        <?php 
                        $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
                        foreach ($days as $day): ?>
                            <option value="<?= $day ?>" <?= ($_GET['day'] ?? '') === $day ? 'selected' : '' ?>><?= $day ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Time Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Time (HH:MM)</label>
                    <input type="time" name="time" value="<?= htmlspecialchars($_GET['time'] ?? '') ?>" class="w-full px-3 py-2 border rounded-md">
                </div>
                <div class="md:col-span-5 flex items-end space-x-2">
                    <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">
                        Search Tutors
                    </button>
                    <a href="dashboard.php" class="w-full bg-gray-300 text-gray-800 py-2 px-4 rounded-md text-center hover:bg-gray-400">
                        Reset Filters
                    </a>
                </div>
            </form>
            <div class="bg-white rounded-lg shadow p-6">
                <?php
                // Fetch unread messages
                $stmt = $db->conn->prepare("SELECT m.*, u.full_name FROM messages m
                    JOIN users u ON m.sender_id = u.user_id
                    WHERE m.receiver_id = ? AND m.is_read = FALSE
                    ORDER BY m.created_at DESC");
                $stmt->execute([$userId]);
                $unreadMessages = $stmt->fetchAll();
                ?>
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-gray-500">Unread Messages</h3>
                        <p class="text-2xl font-bold"><?= count($unreadMessages) ?></p>
                        <?php foreach ($unreadMessages as $msg): ?>
                            <li class="text-sm text-gray-800">
                                <span class="font-semibold"><?= htmlspecialchars($msg['full_name']) ?>:</span>
                                <?= isset($msg['message_text']) && $msg['message_text'] !== null
                                    ? htmlspecialchars(mb_strimwidth("***********", 0, 40, '...'))
                                    : '<span class="text-gray-400">[No message]</span>' ?>
                                <span class="text-xs text-gray-400">(<?= date('M d, H:i', strtotime($msg['created_at'])) ?>)</span>
                            </li>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php if (!empty($chatTutors)): ?>
                <div class="bg-white rounded-lg shadow p-6 my-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Chat History</h3>
                    <ul class="space-y-2">
                        <?php foreach ($chatTutors as $tutor): ?>
                            <li>
                                <a href="chat.php?tutor_id=<?= $tutor['user_id'] ?>" class="text-blue-700 hover:underline font-semibold">
                                    <?= htmlspecialchars($tutor['full_name']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pending Requests -->
        <?php if (!empty($pendingRequests)): ?>
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Your Pending Requests</h3>
            <div class="space-y-4">
                <?php foreach ($pendingRequests as $request): ?>
                <div class="border rounded-lg p-4 flex justify-between items-center">
                    <div>
                        <h4 class="font-medium">Request to <?= htmlspecialchars($request['full_name']) ?></h4>
                        <p class="text-sm text-gray-600">Sent on <?= date('M d, Y', strtotime($request['created_at'])) ?></p>
                    </div>
                    <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm">Pending</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>


        <!-- Available Tutors -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Available Tutors</h3>

            <?php
            // Check if all filters are default/empty
            $filtersDefault = empty($searchLocation) && empty($searchSubject) && empty($experience) && empty($day) && empty($time);
            ?>

            <?php if ($filtersDefault): ?>
                <p class="text-gray-600">No filter selected. Please use the filters above to find tutors.</p>
            <?php elseif (empty($tutors)): ?>
                <p class="text-gray-600">No tutors found matching your criteria. Try adjusting your search filters.</p>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($tutors as $tutor): ?>
                    <div class="border rounded-lg overflow-hidden hover:shadow-md transition-shadow">
                        <div class="p-4">
                            <div class="flex items-center mb-4">
                                <img src="../assets/images/tutor.jpg" alt="Tutor" class="h-12 w-12 rounded-full mr-4">
                                <div>
                                    <h4 class="font-bold"><?= htmlspecialchars($tutor['full_name']) ?></h4>
                                    <p class="text-sm text-gray-600"><?= htmlspecialchars(explode(',', $tutor['subjects_taught'])[0]) ?> Tutor</p>
                                    <?php if (isset($ratings[$tutor['user_id']]) && $ratings[$tutor['user_id']] > 0): ?>
                                        <p class="text-yellow-600 text-sm flex items-center">
                                            ★ <?= $ratings[$tutor['user_id']] ?>/5
                                        </p>
                                    <?php else: ?>
                                        <p class="text-gray-400 text-sm">No ratings yet</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="space-y-2 text-sm mb-4">
                                <p class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    <?= htmlspecialchars($tutor['area']) ?>, <?= htmlspecialchars($tutor['city']) ?>
                                </p>
                                <p class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                    <?= $tutor['experience_years'] ?> years experience
                                </p>
                                <p class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    ₹<?= $tutor['hourly_rate'] ?> /hour
                                </p>
                            </div>
                            <div class="flex space-x-2">
                                <?php
                                $tutorRequests = $allRequests[$tutor['user_id']] ?? [];
                                if (in_array('pending', $tutorRequests)) {
                                    // Pending request exists
                                    ?>
                                    <span class="flex-1 bg-yellow-100 text-yellow-800 py-2 px-4 rounded-md text-center">
                                        Request Pending
                                    </span>
                                <?php
                                } elseif (in_array('accepted', $tutorRequests)) {
                                    // Accepted request exists
                                    ?>
                                    <span class="flex-1 bg-green-100 text-green-800 py-2 px-4 rounded-md text-center">
                                        Request Accepted
                                    </span>
                                    <a href="chat.php?tutor_id=<?= $tutor['user_id'] ?>" class="flex-1 bg-gray-200 text-gray-800 py-2 px-4 rounded-md text-center hover:bg-gray-300">
                                        Message
                                    </a>
                                <?php
                                } elseif (in_array('completed', $tutorRequests)) {
                                    // Completed request exists, allow new request
                                    ?>
                                    <a href="send_request.php?tutor_id=<?= $tutor['user_id'] ?>" class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-md text-center hover:bg-blue-700">
                                        Send Request Again
                                    </a>
                                <?php
                                } else {
                                    // No request yet
                                    ?>
                                    <a href="send_request.php?tutor_id=<?= $tutor['user_id'] ?>" class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-md text-center hover:bg-blue-700">
                                        Send Request
                                    </a>
                                <?php
                                }
                                ?>
                                
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($completedRequests)): ?>
        <div class="bg-white rounded-lg shadow p-6 my-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Completed Sessions</h3>
            <div class="space-y-4">
                <?php foreach ($completedRequests as $request): ?>
                    <?php
                    // Check if already rated
                    $stmt2 = $db->conn->prepare("SELECT COUNT(*) FROM ratings WHERE request_id = ?");
                    $stmt2->execute([$request['request_id']]);
                    $alreadyRated = $stmt2->fetchColumn() > 0;
                    ?>
                    <div class="border rounded-lg p-4 flex justify-between items-center">
                        <div>
                            <h4 class="font-medium">Session with <?= htmlspecialchars($request['full_name']) ?></h4>
                            <p class="text-sm text-gray-600">Completed on <?= date('M d, Y', strtotime($request['created_at'])) ?></p>
                        </div>
                        <?php if (!$alreadyRated): ?>
                            <a href="rate_tutor.php?request_id=<?= $request['request_id'] ?>" class="px-3 py-1 bg-blue-600 text-white rounded-full text-sm hover:bg-blue-700">Rate Tutor</a>
                        <?php else: ?>
                            <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">Rated</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </main>
</body>
</html>