<?php
require_once '../includes/data.php';
require_once '../includes/auth.php';

$auth = new Auth();
$auth->redirectIfNotLoggedIn();

$db = new Database();
$userId = $auth->getUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = $_POST['full_name'];
    $qualifications = $_POST['qualifications'];
    $experience_years = $_POST['experience_years'];
    $subjects_taught = $_POST['subjects_taught'];
    $hourly_rate = $_POST['hourly_rate'];
    $teaching_mode = $_POST['teaching_mode'];
    $demo_video_url = $_POST['demo_video_url'];
    $bio = $_POST['bio'];
    $languages = $_POST['languages'];

    $db->update("UPDATE users SET full_name = ? WHERE user_id = ?", [$fullName, $userId]);
    $db->update("UPDATE tutors SET qualifications = ?, experience_years = ?, subjects_taught = ?, hourly_rate = ?, teaching_mode = ?, demo_video_url = ?, bio = ?, languages = ? WHERE user_id = ?",
        [$qualifications, $experience_years, $subjects_taught, $hourly_rate, $teaching_mode, $demo_video_url, $bio, $languages, $userId]);
    $success = "Profile updated!";
}

// Save availability
if (isset($_POST['available_days'])) {
    // Remove all old slots
    $stmt = $db->conn->prepare("DELETE FROM tutor_availability WHERE tutor_id = ?");
    $stmt->execute([$profile['tutor_id'] ?? $userId]);

    foreach ($_POST['available_days'] as $day) {
        $start = $_POST['start_time'][$day] ?? null;
        $end = $_POST['end_time'][$day] ?? null;
        if ($start && $end) {
            $stmt = $db->conn->prepare("INSERT INTO tutor_availability (tutor_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)");
            $stmt->execute([$profile['tutor_id'] ?? $userId, $day, $start, $end]);
        }
    }
}



$profile = $db->getSingle("SELECT u.full_name, t.qualifications, t.experience_years, t.subjects_taught, t.hourly_rate, t.teaching_mode, t.demo_video_url, t.bio, t.languages
    FROM users u JOIN tutors t ON u.user_id = t.user_id WHERE u.user_id = ?", [$userId]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Tutor Profile</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-6">
    <div class="absolute top-6 left-6">
        <a href="dashboard.php" class="text-blue-600 hover:underline"> <--  Back to Dashboard</a>
    </div>
    <div class="bg-white shadow-lg rounded-lg w-full max-w-4xl p-8">
        <h2 class="text-2xl font-bold mb-6 text-center text-gray-800">Edit Tutor Profile</h2>

        <?php if (!empty($success)) : ?>
            <p class="mb-4 text-green-600 font-semibold text-center"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        <form method="post" class="space-y-5">
            <div>
                <label class="block font-medium text-gray-700 mb-1">Full Name</label>
                <input type="text" name="full_name" value="<?= htmlspecialchars($profile['full_name']) ?>" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block font-medium text-gray-700 mb-1">Qualifications</label>
                <input type="text" name="qualifications" value="<?= htmlspecialchars($profile['qualifications']) ?>" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block font-medium text-gray-700 mb-1">Experience (years)</label>
                <input type="number" name="experience_years" value="<?= htmlspecialchars($profile['experience_years']) ?>" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block font-medium text-gray-700 mb-1">Subjects Taught (comma separated)</label>
                <input type="text" name="subjects_taught" value="<?= htmlspecialchars($profile['subjects_taught']) ?>" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block font-medium text-gray-700 mb-1">Hourly Rate</label>
                <input type="number" step="0.01" name="hourly_rate" value="<?= htmlspecialchars($profile['hourly_rate']) ?>" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <?php
            // Fetch current availability
            $daysOfWeek = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
            $availability = [];
            $stmt = $db->conn->prepare("SELECT day_of_week, start_time, end_time FROM tutor_availability WHERE tutor_id = ?");
            $stmt->execute([$profile['tutor_id'] ?? $userId]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $availability[$row['day_of_week']] = $row;
            }
            ?>
            <div>
                <label class="block font-medium text-gray-700 mb-1">Availability</label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <?php foreach ($daysOfWeek as $day): 
                        $start = $availability[$day]['start_time'] ?? '';
                        $end = $availability[$day]['end_time'] ?? '';
                    ?>
                    <div class="flex items-center space-x-2">
                        <input type="checkbox" name="available_days[]" value="<?= $day ?>" id="day-<?= $day ?>"
                            <?= $start && $end ? 'checked' : '' ?>>
                        <label for="day-<?= $day ?>" class="w-24"><?= $day ?></label>
                        <input type="time" name="start_time[<?= $day ?>]" value="<?= $start ?>" class="border rounded px-2 py-1">
                        <span>to</span>
                        <input type="time" name="end_time[<?= $day ?>]" value="<?= $end ?>" class="border rounded px-2 py-1">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div>
                <label class="block font-medium text-gray-700 mb-1">Teaching Mode</label>
                <select name="teaching_mode" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="in-house" <?= $profile['teaching_mode']=='in-house'?'selected':'' ?>>In-house</option>
                    <option value="coaching-center" <?= $profile['teaching_mode']=='coaching-center'?'selected':'' ?>>Coaching Center</option>
                    <option value="both" <?= $profile['teaching_mode']=='both'?'selected':'' ?>>Both</option>
                </select>
            </div>

            <div>
                <label class="block font-medium text-gray-700 mb-1">Demo Video URL</label>
                <input type="text" name="demo_video_url" value="<?= htmlspecialchars($profile['demo_video_url'] ?? '') ?>" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block font-medium text-gray-700 mb-1">Bio</label>
                <textarea name="bio" rows="4" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($profile['bio']) ?></textarea>
            </div>

            <div>
                <label class="block font-medium text-gray-700 mb-1">Languages</label>
                <input type="text" name="languages" value="<?= htmlspecialchars($profile['languages']) ?>" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
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
