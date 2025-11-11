<?php
// session_start();
require_once 'includes/auth.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LocalTutorConnect - Find the Best Tutors Near You</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-6xl mx-auto px-4">
            <div class="flex justify-between">
                <div class="flex space-x-7">
                    <a href="index.php" class="flex items-center py-4 px-2">
                        <span class="font-semibold text-gray-900 text-2xl">LocalTutorConnect</span>
                    </a>
                </div>
                <div class="hidden md:flex items-center space-x-3">
                    <a href="index.php" class="py-4 px-2 text-blue-500 border-b-4 border-blue-500 font-semibold">Home</a>
                    <a href="#how-it-works" class="py-4 px-2 text-gray-500 font-semibold hover:text-blue-500 transition duration-300">How It Works</a>
                    <a href="#subjects" class="py-4 px-2 text-gray-500 font-semibold hover:text-blue-500 transition duration-300">Subjects</a>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <?php if($_SESSION['user_type'] === 'student'): ?>
                            <a href="student/dashboard.php" class="py-4 px-2 text-gray-500 font-semibold hover:text-blue-500 transition duration-300">Dashboard</a>
                        <?php else: ?>
                            <a href="tutor/dashboard.php" class="py-4 px-2 text-gray-500 font-semibold hover:text-blue-500 transition duration-300">Dashboard</a>
                        <?php endif; ?>
                        <a href="logout.php" class="py-2 px-4 bg-red-500 text-white font-semibold rounded-lg hover:bg-red-600 transition duration-300">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="py-2 px-4 text-blue-500 font-semibold hover:bg-blue-50 rounded-lg transition duration-300">Login</a>
                        <a href="register.php" class="py-2 px-4 bg-blue-500 text-white font-semibold rounded-lg hover:bg-blue-600 transition duration-300">Register</a>
                    <?php endif; ?>
                </div>
                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center">
                    <button class="outline-none mobile-menu-button">
                        <svg class="w-6 h-6 text-gray-500" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                            <path d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        <!-- Mobile menu -->
        <div class="hidden mobile-menu">
            <ul class="">
                <li class="active"><a href="index.php" class="block text-sm px-2 py-4 text-white bg-blue-500 font-semibold">Home</a></li>
                <li><a href="#how-it-works" class="block text-sm px-2 py-4 hover:bg-blue-500 transition duration-300">How It Works</a></li>
                <li><a href="#subjects" class="block text-sm px-2 py-4 hover:bg-blue-500 transition duration-300">Subjects</a></li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if($_SESSION['user_type'] === 'student'): ?>
                        <li><a href="student/dashboard.php" class="block text-sm px-2 py-4 hover:bg-blue-500 transition duration-300">Dashboard</a></li>
                    <?php else: ?>
                        <li><a href="tutor/dashboard.php" class="block text-sm px-2 py-4 hover:bg-blue-500 transition duration-300">Dashboard</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php" class="block text-sm px-2 py-4 hover:bg-blue-500 transition duration-300">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php" class="block text-sm px-2 py-4 hover:bg-blue-500 transition duration-300">Login</a></li>
                    <li><a href="register.php" class="block text-sm px-2 py-4 hover:bg-blue-500 transition duration-300">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="bg-blue-600 text-white py-20">
        <div class="container mx-auto px-6 text-center">
            <h1 class="text-4xl font-bold mb-6">Find the Perfect Tutor in Your Area</h1>
            <p class="text-xl mb-8">Connect with qualified tutors for personalized learning experiences</p>
            <div class="max-w-md mx-auto">
                <form action="student/dashboard.php" method="GET" class="flex flex-col md:flex-row gap-2">
                    <input type="text" name="location" placeholder="Enter your location" class="flex-grow p-3 rounded-lg text-gray-800 focus:outline-none">
                    <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 px-6 rounded-lg transition duration-300">
                        Find Tutors
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section id="how-it-works" class="py-16 bg-white">
        <div class="container mx-auto px-6">
            <h2 class="text-3xl font-bold text-center mb-12">How LocalTutorConnect Works</h2>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-gray-50 p-6 rounded-lg shadow-md text-center">
                    <div class="text-blue-500 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">1. Find Tutors</h3>
                    <p class="text-gray-600">Search for qualified tutors in your local area by subject, availability, and price range.</p>
                </div>
                <div class="bg-gray-50 p-6 rounded-lg shadow-md text-center">
                    <div class="text-blue-500 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">2. Send Request</h3>
                    <p class="text-gray-600">Connect with tutors by sending a request and discussing your learning needs.</p>
                </div>
                <div class="bg-gray-50 p-6 rounded-lg shadow-md text-center">
                    <div class="text-blue-500 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">3. Start Learning</h3>
                    <p class="text-gray-600">Begin your personalized learning journey with your matched tutor.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Subjects Section -->
    <section id="subjects" class="py-16 bg-gray-50">
        <div class="container mx-auto px-6">
            <h2 class="text-3xl font-bold text-center mb-12">Popular Subjects</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <div class="bg-white p-4 rounded-lg shadow-md text-center hover:shadow-lg transition duration-300">
                    <div class="text-blue-500 mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <h3 class="font-semibold">Mathematics</h3>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-md text-center hover:shadow-lg transition duration-300">
                    <div class="text-blue-500 mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                        </svg>
                    </div>
                    <h3 class="font-semibold">Science</h3>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-md text-center hover:shadow-lg transition duration-300">
                    <div class="text-blue-500 mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
                        </svg>
                    </div>
                    <h3 class="font-semibold">English</h3>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-md text-center hover:shadow-lg transition duration-300">
                    <div class="text-blue-500 mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                        </svg>
                    </div>
                    <h3 class="font-semibold">Computer Science</h3>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-md text-center hover:shadow-lg transition duration-300">
                    <div class="text-blue-500 mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <h3 class="font-semibold">History</h3>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-md text-center hover:shadow-lg transition duration-300">
                    <div class="text-blue-500 mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <h3 class="font-semibold">Physics</h3>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="py-16 bg-blue-700 text-white">
        <div class="container mx-auto px-6 text-center">
            <h2 class="text-3xl font-bold mb-6">Ready to Start Learning?</h2>
            <p class="text-xl mb-8">Join thousands of students finding the perfect tutors in their area</p>
            <a href="register.php" class="inline-block bg-white text-blue-700 font-bold py-3 px-8 rounded-lg hover:bg-gray-100 transition duration-300">
                Sign Up Now
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8">
        <div class="container mx-auto px-6">
            <div class="grid md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-lg font-semibold mb-4">LocalTutorConnect</h3>
                    <p class="text-gray-400">Connecting students with the best local tutors for personalized learning experiences.</p>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="index.php" class="text-gray-400 hover:text-white">Home</a></li>
                        <li><a href="#how-it-works" class="text-gray-400 hover:text-white">How It Works</a></li>
                        <li><a href="#subjects" class="text-gray-400 hover:text-white">Subjects</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Account</h3>
                    <ul class="space-y-2">
                        <li><a href="login.php" class="text-gray-400 hover:text-white">Login</a></li>
                        <li><a href="register.php" class="text-gray-400 hover:text-white">Register</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Contact</h3>
                    <ul class="space-y-2">
                        <li class="text-gray-400">Email: info@localtutorconnect.com</li>
                        <li class="text-gray-400">Phone: +91 1234567890</li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; <?php echo date('Y'); ?> LocalTutorConnect. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Mobile Menu JavaScript -->
    <script src="assets/js/scripts.js"></script>
    <script>
        // Mobile menu toggle
        const btn = document.querySelector('.mobile-menu-button');
        const menu = document.querySelector('.mobile-menu');

        btn.addEventListener('click', () => {
            menu.classList.toggle('hidden');
        });
    </script>
</body>
</html>