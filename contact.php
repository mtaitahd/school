<?php
require_once __DIR__ . '/php/includes/session.php';
require_once __DIR__ . '/php/includes/security.php';
sec_send_headers();
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Kona Ya Hisabati</title>
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar-modern">
        <div class="container-modern">
            <div class="navbar-content">
                <!-- Left Side - Logo -->
                <div class="navbar-brand-modern">
                    <div class="navbar-brand-text">
                        <span class="brand-main">Kona Ya Hisabati</span>
                        <span class="brand-subtitle">Jifunze • Furahia • Fanikiwa</span>
                    </div>
                </div>

                <!-- Center Menu -->
                <ul class="navbar-menu">
                    <li class="navbar-item">
                        <a href="index" class="navbar-link">
                            <i class="fas fa-home"></i>
                            <span>Home</span>
                        </a>
                    </li>
                    <li class="navbar-item">
                        <a href="about" class="navbar-link">
                            <i class="fas fa-info-circle"></i>
                            <span>About</span>
                        </a>
                    </li>
                    <li class="navbar-item active">
                        <a href="contact" class="navbar-link">
                            <i class="fas fa-question-circle"></i>
                            <span>Help</span>
                        </a>
                    </li>
                </ul>

                <!-- Right Side -->
                <div class="navbar-right">
                    <!-- Teacher Login Button -->
                    <a href="login.php?role=teacher" class="teacher-login-btn">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <span>Teacher Login</span>
                    </a>

                    <!-- Mobile Hamburger -->
                    <button class="hamburger-btn">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-child mt-30">
        <div class="activity-container">
            <h1 class="activity-title text-center">Contact Us</h1>
            <p class="text-center activity-instruction mb-30">We'd love to hear from you!</p>

            <div class="row-child">
                <div class="col-child-2">
                    <div class="dashboard-card">
                        <h2 style="color: var(--primary-blue); margin-bottom: 20px;">Get in Touch</h2>
                        
                        <div style="margin-bottom: 20px;">
                            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                                <div style="width: 50px; height: 50px; border-radius: 50%; background: var(--primary-blue); display: flex; align-items: center; justify-content: center; color: white;">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div>
                                    <p style="margin: 0; font-weight: 600;">Email</p>
                                    <p style="margin: 0; color: var(--text-light);">info@konahisabati.com</p>
                                </div>
                            </div>
                            
                            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                                <div style="width: 50px; height: 50px; border-radius: 50%; background: var(--primary-green); display: flex; align-items: center; justify-content: center; color: white;">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <div>
                                    <p style="margin: 0; font-weight: 600;">Phone</p>
                                    <p style="margin: 0; color: var(--text-light);">+255 XXX XXX XXX</p>
                                </div>
                            </div>
                            
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <div style="width: 50px; height: 50px; border-radius: 50%; background: var(--primary-yellow); display: flex; align-items: center; justify-content: center; color: var(--text-dark);">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div>
                                    <p style="margin: 0; font-weight: 600;">Location</p>
                                    <p style="margin: 0; color: var(--text-light);">Tanzania</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-child-2">
                    <div class="dashboard-card">
                        <h2 style="color: var(--primary-blue); margin-bottom: 20px;">Send us a Message</h2>
                        <form>
                            <div class="form-group-child">
                                <label class="form-label-child">Your Name</label>
                                <input type="text" class="form-control-child" placeholder="Enter your name" required>
                            </div>
                            <div class="form-group-child">
                                <label class="form-label-child">Email Address</label>
                                <input type="email" class="form-control-child" placeholder="Enter your email" required>
                            </div>
                            <div class="form-group-child">
                                <label class="form-label-child">Subject</label>
                                <input type="text" class="form-control-child" placeholder="What is this about?" required>
                            </div>
                            <div class="form-group-child">
                                <label class="form-label-child">Message</label>
                                <textarea class="form-control-child" rows="5" placeholder="Your message..." required></textarea>
                            </div>
                            <button type="submit" class="btn-child btn-child-primary btn-child-large" style="width: 100%;">
                                <i class="fas fa-paper-plane me-2"></i>Send Message
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="text-center mt-30">
                <a href="index" class="btn-child btn-child-yellow">
                    <i class="fas fa-arrow-left me-2"></i>Back to Home
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
