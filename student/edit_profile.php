<?php
require_once '../includes/data.php';
require_once '../includes/auth.php';

$auth = new Auth();
$auth->redirectIfNotLoggedIn();

$db = new Database();
$userId = $auth->getUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = $_POST['full_name'];
    $grade = $_POST['grade'];
    $subjects = $_POST['subjects'];
    $preferred_days = $_POST['preferred_days'];
    $preferred_times = $_POST['preferred_times'];
    $learning_goals = $_POST['learning_goals'];

    $db->update("UPDATE users SET full_name = ? WHERE user_id = ?", [$fullName, $userId]);
    $db->update("UPDATE students SET grade = ?, subjects = ?, preferred_days = ?, preferred_times = ?, learning_goals = ? WHERE user_id = ?",
        [$grade, $subjects, $preferred_days, $preferred_times, $learning_goals, $userId]);
    $success = "Profile updated!";
}

$profile = $db->getSingle("SELECT u.full_name, s.grade, s.subjects, s.preferred_days, s.preferred_times, s.learning_goals
    FROM users u JOIN students s ON u.user_id = s.user_id WHERE u.user_id = ?", [$userId]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Student Profile</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-6">
    <!-- back to dashboard -->
    <div class="absolute top-6 left-6">
        <a href="dashboard.php" class="text-blue-600 hover:underline"> <--  Back to Dashboard</a>
    </div>
    <div class="bg-white shadow-lg rounded-lg w-full max-w-2xl p-8">
        <h2 class="text-2xl font-bold mb-6 text-center text-gray-800">Edit Profile</h2>

        <?php if (!empty($success)) : ?>
            <p class="mb-4 text-green-600 font-semibold text-center"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        <form method="post" class="space-y-5">
            <div>
                <label class="block font-medium text-gray-700 mb-1">Full Name</label>
                <input type="text" name="full_name" value="<?= htmlspecialchars($profile['full_name']) ?>" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block font-medium text-gray-700 mb-1">Grade</label>
                <input type="text" name="grade" value="<?= htmlspecialchars($profile['grade']) ?>" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block font-medium text-gray-700 mb-1">Subjects (comma separated)</label>
                <input type="text" name="subjects" value="<?= htmlspecialchars($profile['subjects']) ?>" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block font-medium text-gray-700 mb-1">Preferred Days</label>
                <input type="text" name="preferred_days" value="<?= htmlspecialchars($profile['preferred_days']) ?>" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block font-medium text-gray-700 mb-1">Preferred Times</label>
                <input type="text" name="preferred_times" value="<?= htmlspecialchars($profile['preferred_times']) ?>" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block font-medium text-gray-700 mb-1">Learning Goals</label>
                <textarea name="learning_goals" rows="4" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($profile['learning_goals']) ?></textarea>
            </div>

            <div class="text-center">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition font-semibold">
                    Save
                </button>
            </div>
        </form>
    </div>
</body>
</html>
