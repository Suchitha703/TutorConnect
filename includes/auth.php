<?php
session_start();

class Auth {
    private $conn;

    public function __construct($conn = null) {
        if ($conn) {
            $this->conn = $conn;
        }
    }

    public function register($userType, $name, $email, $password, $contact, $city, $area, $pincode) {
        // Validate inputs
        if (empty($name) || empty($email) || empty($password) || empty($contact)) {
            throw new Exception("All fields are required");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        if (strlen($password) < 6) {
            throw new Exception("Password must be at least 6 characters");
        }

        // Check if email exists
        $stmt = $this->conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            throw new Exception("Email already registered");
        }

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert user
        $stmt = $this->conn->prepare("INSERT INTO users (user_type, full_name, email, password, contact_number, city, area, pincode) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userType, $name, $email, $hashedPassword, $contact, $city, $area, $pincode]);

        return $this->conn->lastInsertId();
    }

    public function login($email, $password) {
        // Validate inputs
        if (empty($email) || empty($password)) {
            throw new Exception("Email and password are required");
        }

        // Get user by email
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            throw new Exception("Invalid email or password");
        }

        // Verify password
        if (!password_verify($password, $user['password'])) {
            throw new Exception("Invalid email or password");
        }

        // Set session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_type'] = $user['user_type'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['name'] = $user['full_name'];
        $_SESSION['logged_in'] = true;

        return $user;
    }

    public function logout() {
        // Unset all session variables
        $_SESSION = array();

        // Destroy the session
        session_destroy();
    }

    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    public function getUserType() {
        return $_SESSION['user_type'] ?? null;
    }

    public function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }

    public function redirectIfNotLoggedIn() {
        if (!$this->isLoggedIn()) {
            header('Location: ../login.php');
            exit();
        }
    }

    public function redirectIfNotAuthorized($allowedTypes = []) {
        $this->redirectIfNotLoggedIn();

        if (!empty($allowedTypes) && !in_array($this->getUserType(), $allowedTypes)) {
            header('Location: ../dashboard.php');
            exit();
        }
    }
}
?>