<?php
require_once 'data.php';
require_once 'auth.php';

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to specified URL
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Get user details by ID
 */
function getUserById($userId) {
    $db = new Database();
    return $db->getSingle("SELECT * FROM users WHERE user_id = ?", [$userId]);
}

/**
 * Get student profile by user ID
 */
function getStudentProfile($userId) {
    $db = new Database();
    return $db->getSingle("SELECT * FROM students WHERE user_id = ?", [$userId]);
}

/**
 * Get tutor profile by user ID
 */
function getTutorProfile($userId) {
    $db = new Database();
    return $db->getSingle("SELECT * FROM tutors WHERE user_id = ?", [$userId]);
}

/**
 * Get tutor availability
 */
function getTutorAvailability($tutorId) {
    $db = new Database();
    return $db->getMultiple("SELECT * FROM tutor_availability WHERE tutor_id = ?", [$tutorId]);
}

/**
 * Get messages between two users
 */
function getMessages($user1, $user2, $limit = 100) {
    $db = new Database();
    $sql = "SELECT * FROM messages 
            WHERE (sender_id = ? AND receiver_id = ?) 
            OR (sender_id = ? AND receiver_id = ?)
            ORDER BY created_at DESC
            LIMIT ?";
    return $db->getMultiple($sql, [$user1, $user2, $user2, $user1, $limit]);
}

/**
 * Get pending requests for tutor
 */
function getPendingRequests($tutorId) {
    $db = new Database();
    $sql = "SELECT r.*, u.full_name, s.grade FROM requests r
            JOIN users u ON r.student_id = u.user_id
            JOIN students s ON r.student_id = s.user_id
            WHERE r.tutor_id = ? AND r.status = 'pending'";
    return $db->getMultiple($sql, [$tutorId]);
}

/**
 * Get tutor ratings
 */
function getTutorRatings($tutorId) {
    $db = new Database();
    $sql = "SELECT r.*, u.full_name FROM ratings r
            JOIN users u ON r.student_id = u.user_id
            WHERE r.tutor_id = ?
            ORDER BY r.created_at DESC";
    return $db->getMultiple($sql, [$tutorId]);
}

/**
 * Calculate average rating for tutor
 */
function getAverageRating($tutorId) {
    $db = new Database();
    $result = $db->getSingle("SELECT AVG(rating_value) as avg_rating FROM ratings WHERE tutor_id = ?", [$tutorId]);
    return round($result['avg_rating'] ?? 0, 1);
}

/**
 * Check if request exists between student and tutor
 */
function requestExists($studentId, $tutorId) {
    $db = new Database();
    $result = $db->getSingle(
        "SELECT request_id FROM requests 
        WHERE student_id = ? AND tutor_id = ? 
        AND status IN ('pending', 'accepted')",
        [$studentId, $tutorId]
    );
    return !empty($result);
}

/**
 * Generate CSRF token
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Format date for display
 */
function formatDate($dateString, $format = 'M j, Y g:i A') {
    $date = new DateTime($dateString);
    return $date->format($format);
}
?>