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

// Get or create request between student and tutor
$stmt = $db->conn->prepare("SELECT * FROM requests WHERE student_id = ? AND tutor_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$studentId, $tutorId]);
$request = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = $_POST['message'] ?? '';

    if (!empty($message)) {
        $requestId = $request ? $request['request_id'] : null;

        $stmt = $db->conn->prepare("INSERT INTO messages (sender_id, receiver_id, request_id, message_text) 
                                   VALUES (?, ?, ?, ?)");
        $stmt->execute([$studentId, $tutorId, $requestId, $message]);
    }
}

// Get messages between student and tutor
$query = "SELECT * FROM messages 
          WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
          ORDER BY created_at ASC";
$stmt = $db->conn->prepare($query);
$stmt->execute([$studentId, $tutorId, $tutorId, $studentId]);
$messages = $stmt->fetchAll();

// Mark messages as read
$stmt = $db->conn->prepare("UPDATE messages SET is_read = TRUE 
                           WHERE receiver_id = ? AND sender_id = ? AND is_read = FALSE");
$stmt->execute([$studentId, $tutorId]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat | LocalTutorConnect</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100">
    <div class="flex flex-col h-screen">
        <!-- Header -->
        <div class="bg-white shadow p-4 flex items-center">
            <a href="dashboard.php" class="mr-4">
                <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <img src="../assets/images/tutor.jpg" alt="Tutor" class="w-10 h-10 rounded-full mr-3">
            <div>
                <h2 class="font-bold"><?= htmlspecialchars($tutor['full_name']) ?></h2>
                <p class="text-xs text-gray-600">
                    <?= $request ? ucfirst($request['status']) : 'No request sent' ?>
                </p>
            </div>
        </div>

        <!-- Chat Messages -->
        <div class="flex-1 overflow-y-auto p-4 space-y-4" id="chat-messages">
            <?php if (empty($messages)): ?>
                <div class="text-center text-gray-500 py-8">
                    No messages yet. Start the conversation!
                </div>
            <?php else: ?>
                <?php foreach ($messages as $message): ?>
                    <div class="flex <?= $message['sender_id'] == $studentId ? 'justify-end' : 'justify-start' ?>">
                        <div class="<?= $message['sender_id'] == $studentId ? 'bg-blue-500 text-white' : 'bg-white' ?> rounded-lg p-3 max-w-xs md:max-w-md shadow">
                            <p><?= htmlspecialchars($message['message_text']) ?></p>
                            <p class="text-xs mt-1 <?= $message['sender_id'] == $studentId ? 'text-blue-100' : 'text-gray-500' ?>">
                                <?= date('h:i A', strtotime($message['created_at'])) ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Message Input -->
        <div class="bg-white border-t p-4">
            <form method="POST" class="flex" id="message-form">
                <input type="text" name="message" placeholder="Type your message..." class="flex-1 border rounded-l-lg px-4 py-2 focus:outline-none focus:ring-1 focus:ring-blue-500">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-r-lg hover:bg-blue-700">
                    Send
                </button>
            </form>
        </div>
    </div>

    <script>
        // Scroll to bottom of chat
        const chatMessages = document.getElementById('chat-messages');
        chatMessages.scrollTop = chatMessages.scrollHeight;

        // Form submission with AJAX
        document.getElementById('message-form').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const messageInput = this.elements['message'];

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.ok) {
                    // Reload the page to show new message
                    window.location.reload();
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to send message'
                });
            });
        });
    </script>
</body>
</html>