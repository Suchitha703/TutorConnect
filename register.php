<?php
require_once 'includes/data.php';
require_once 'includes/auth.php';

$db = new Database();
$auth = new Auth($db->conn);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userType = $_POST['user_type'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $contact = $_POST['contact'];
    $city = $_POST['city'];
    $area = $_POST['area'];
    $pincode = $_POST['pincode'];

    try {
        // Register base user
        $userId = $auth->register($userType, $name, $email, $password, $contact, $city, $area, $pincode);
        
        // Handle student-specific registration
        if ($userType === 'student') {
            $grade = $_POST['grade'];
            $subjects = implode(',', $_POST['subjects']);
            $preferredDays = implode(',', $_POST['preferred_days']);
            $preferredTimes = $_POST['preferred_times'];
            $learningGoals = $_POST['learning_goals'];
            
            $stmt = $db->conn->prepare("INSERT INTO students (user_id, grade, subjects, preferred_days, preferred_times, learning_goals) 
                                      VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $grade, $subjects, $preferredDays, $preferredTimes, $learningGoals]);
        } 
        // Handle tutor-specific registration
        elseif ($userType === 'tutor') {
            $qualifications = $_POST['qualifications'];
            $experience = $_POST['experience'];
            $subjectsTaught = implode(',', $_POST['subjects_taught']);
            $hourlyRate = $_POST['hourly_rate'];
            $teachingMode = $_POST['teaching_mode'];
            $bio = $_POST['bio'];
            $languages = implode(',', $_POST['languages']);
            
            $stmt = $db->conn->prepare("INSERT INTO tutors (user_id, qualifications, experience_years, subjects_taught, hourly_rate, teaching_mode, bio, languages) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $qualifications, $experience, $subjectsTaught, $hourlyRate, $teachingMode, $bio, $languages]);
            
            $tutorId = $db->conn->lastInsertId();

            // Handle tutor availability
            foreach ($_POST['availability'] as $day => $times) {
                if ($times['active']) {
                    $stmt = $db->conn->prepare("INSERT INTO tutor_availability (tutor_id, day_of_week, start_time, end_time) 
                                              VALUES (?, ?, ?, ?)");
                    $stmt->execute([$tutorId, $day, $times['start'], $times['end']]);
                }
            }
        }
        
        $success = "Registration successful! Please login.";
        header("Refresh: 3; url=login.php");
    } catch (Exception $e) {
        $error = "Registration failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | LocalTutorConnect</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl overflow-hidden w-full max-w-4xl">
            <div class="md:flex">
                <div class="md:w-1/2 bg-blue-600 text-white p-8 flex flex-col justify-center">
                    <h1 class="text-3xl font-bold mb-4">Join LocalTutorConnect</h1>
                    <p class="mb-6">Connect with the best tutors in your area or start your tutoring journey with us.</p>
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>Find qualified tutors near you</span>
                        </div>
                        <div class="flex items-center">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>Personalized learning experience</span>
                        </div>
                        <div class="flex items-center">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>Flexible scheduling</span>
                        </div>
                    </div>
                </div>
                
                <div class="md:w-1/2 p-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Create Your Account</h2>
                    
                    <?php if ($error): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                            <?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="space-y-4">
                        <div>
                            <label class="block text-gray-700 mb-2">I am a</label>
                            <div class="flex space-x-4">
                                <label class="inline-flex items-center">
                                    <input type="radio" name="user_type" value="student" checked class="form-radio text-blue-600">
                                    <span class="ml-2">Student</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" name="user_type" value="tutor" class="form-radio text-blue-600">
                                    <span class="ml-2">Tutor</span>
                                </label>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 mb-2">Full Name</label>
                            <input type="text" name="name" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 mb-2">Email Address</label>
                            <input type="email" name="email" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 mb-2">Password</label>
                            <input type="password" name="password" required minlength="6" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 mb-2">Contact Number</label>
                            <input type="tel" name="contact" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-gray-700 mb-2">City</label>
                                <input type="text" name="city" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">Area</label>
                                <input type="text" name="area" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">Pincode</label>
                                <input type="text" name="pincode" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                        
                        <!-- Student Specific Fields -->
                        <div id="student-fields">
                            <div>
                                <label class="block text-gray-700 mb-2">Grade/Class</label>
                                <input type="text" name="grade" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 mb-2">Subjects Needed</label>
                                <select name="subjects[]" multiple class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="Math">Mathematics</option>
                                    <option value="Science">Science</option>
                                    <option value="English">English</option>
                                    <option value="History">History</option>
                                    <option value="Computer Science">Computer Science</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 mb-2">Preferred Days</label>
                                <div class="grid grid-cols-3 gap-2">
                                    <?php 
                                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                    foreach ($days as $day): ?>
                                        <label class="inline-flex items-center">
                                            <input type="checkbox" name="preferred_days[]" value="<?= $day ?>" class="form-checkbox text-blue-600">
                                            <span class="ml-2"><?= substr($day, 0, 3) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 mb-2">Preferred Times</label>
                                <input type="text" name="preferred_times" placeholder="e.g. After 4pm" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 mb-2">Learning Goals</label>
                                <textarea name="learning_goals" rows="2" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                            </div>
                        </div>
                        
                        <!-- Tutor Specific Fields (hidden by default) -->
                        <div id="tutor-fields" class="hidden">
                            <div>
                                <label class="block text-gray-700 mb-2">Qualifications</label>
                                <textarea name="qualifications" rows="2" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 mb-2">Years of Experience</label>
                                <input type="number" name="experience" min="0" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 mb-2">Subjects You Teach</label>
                                <select name="subjects_taught[]" multiple class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="Math">Mathematics</option>
                                    <option value="Science">Science</option>
                                    <option value="English">English</option>
                                    <option value="History">History</option>
                                    <option value="Computer Science">Computer Science</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 mb-2">Hourly Rate (₹)</label>
                                <input type="number" name="hourly_rate" min="0" step="50" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 mb-2">Teaching Mode</label>
                                <select name="teaching_mode" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="in-house">In-House</option>
                                    <option value="coaching-center">Coaching Center</option>
                                    <option value="both">Both</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 mb-2">Availability</label>
                                <div class="space-y-2">
                                    <?php foreach ($days as $day): ?>
                                        <div class="flex items-center space-x-4">
                                            <label class="inline-flex items-center">
                                                <input type="checkbox" name="availability[<?= $day ?>][active]" class="form-checkbox text-blue-600 day-checkbox">
                                                <span class="ml-2 w-16"><?= $day ?></span>
                                            </label>
                                            <input type="time" name="availability[<?= $day ?>][start]" disabled class="px-2 py-1 border rounded focus:outline-none focus:ring-1 focus:ring-blue-500 availability-time">
                                            <span>to</span>
                                            <input type="time" name="availability[<?= $day ?>][end]" disabled class="px-2 py-1 border rounded focus:outline-none focus:ring-1 focus:ring-blue-500 availability-time">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 mb-2">Bio/Teaching Style</label>
                                <textarea name="bio" rows="2" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 mb-2">Languages You Speak</label>
                                <select name="languages[]" multiple class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="English">English</option>
                                    <option value="Hindi">Hindi</option>
                                    <option value="Spanish">Spanish</option>
                                    <option value="French">French</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="pt-4">
                            <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-300">
                                Register
                            </button>
                        </div>
                    </form>
                    
                    <div class="mt-4 text-center">
                        <p class="text-gray-600">Already have an account? <a href="login.php" class="text-blue-600 hover:underline">Login here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle between student and tutor fields
        document.querySelectorAll('input[name="user_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'student') {
                    document.getElementById('student-fields').classList.remove('hidden');
                    document.getElementById('tutor-fields').classList.add('hidden');
                } else {
                    document.getElementById('student-fields').classList.add('hidden');
                    document.getElementById('tutor-fields').classList.remove('hidden');
                }
            });
        });

        // Enable/disable time inputs based on checkbox
        document.querySelectorAll('.day-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const dayRow = this.closest('div');
                const timeInputs = dayRow.querySelectorAll('.availability-time');
                timeInputs.forEach(input => {
                    input.disabled = !this.checked;
                    if (!this.checked) {
                        input.value = '';
                    }
                });
            });
        });
    </script>
</body>
</html>