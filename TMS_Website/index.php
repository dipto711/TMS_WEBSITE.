<?php
session_start();
// Your existing session checks
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TMS - Transportation Management System</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Add preconnect for performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
</head>
<body>
    <!-- Navigation -->
    <nav class="main-nav">
        <div class="logo">
            <img src="images/tms-logo.png" alt="TMS Logo"> <!-- Add your logo -->
            <span>TMS</span>
        </div>
        <div class="nav-links">
            <a href="#features">Features</a>
            <a href="#about">About</a>
            <a href="auth.php" class="login-btn">Login/Register</a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Transform Your Transportation Management</h1>
            <p>Streamline operations, optimize routes, and boost efficiency with our advanced TMS solution</p>
            <a href="auth.php" class="cta-button">Get Started</a>
        </div>
        <div class="hero-image">
            <!-- Add a relevant hero image -->
            <img src="images/tms.png" alt="Transportation">
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features">
        <h2>Why Choose Our TMS?</h2>
        <div class="feature-grid">
            <div class="feature-card">
                <i class="fas fa-route"></i>
                <h3>Route Optimization</h3>
                <p>Smart algorithms for the most efficient delivery paths</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-truck-moving"></i>
                <h3>Real-time Tracking</h3>
                <p>Monitor your fleet's location in real-time</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-chart-line"></i>
                <h3>Analytics Dashboard</h3>
                <p>Comprehensive insights and reporting</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-users"></i>
                <h3>User Management</h3>
                <p>Easy control over user roles and permissions</p>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about">
        <div class="about-content">
            <h2>About Our Platform</h2>
            <p>Our Transportation Management System revolutionizes how businesses handle their logistics operations. With cutting-edge technology and user-friendly interfaces, we make transportation management simple and efficient.</p>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>Contact Us</h3>
                <p>Email: info@tms.com</p>
                <p>Phone: (123) 456-7890</p>
            </div>
            <div class="footer-section">
                <h3>Follow Us</h3>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2024 TMS. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
