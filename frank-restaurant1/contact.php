<?php
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us | Frank Restaurant Management System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@400;700;800;900&family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Contact Page Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --accent-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --premium-gradient: linear-gradient(135deg, #FF6B6B 0%, #4ECDC4 50%, #45B7D1 100%);
            
            /* Light Theme Variables */
            --bg-primary: #ffffff;
            --bg-card: #f8fafc;
            --bg-tertiary: #f1f5f9;
            --text-primary: #1a1a1a;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --border-color: #e5e5e5;
            --accent-primary: #667eea;
            --accent-secondary: #764ba2;
            --gradient-primary: var(--primary-gradient);
            --gradient-secondary: var(--secondary-gradient);
            --gradient-accent: var(--accent-gradient);
            --shadow: 0 4px 15px rgba(0,0,0,0.08);
            --shadow-lg: 0 20px 40px rgba(0,0,0,0.15);
            --transition: all 0.3s ease;
        }

        /* Dark Theme Variables */
        [data-theme="dark"] {
            --bg-primary: #0f172a;
            --bg-card: #1e293b;
            --bg-tertiary: #334155;
            --text-primary: #f8fafc;
            --text-secondary: #cbd5e1;
            --text-muted: #94a3b8;
            --border-color: #334155;
            --accent-primary: #667eea;
            --accent-secondary: #764ba2;
            --shadow: 0 4px 15px rgba(0,0,0,0.3);
            --shadow-lg: 0 20px 40px rgba(0,0,0,0.5);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            overflow-x: hidden;
            line-height: 1.6;
        }

        /* Navigation */
        .nav-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        [data-theme="dark"] .nav-header {
            background: rgba(15, 23, 42, 0.95);
            border-bottom: 1px solid rgba(51, 65, 85, 0.5);
        }

        .nav-header.scrolled {
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }

        [data-theme="dark"] .nav-header.scrolled {
            background: rgba(15, 23, 42, 0.98);
            box-shadow: 0 2px 20px rgba(0,0,0,0.5);
        }

        .nav-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem 2rem;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .nav-link {
            color: var(--text-primary);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            position: relative;
            padding: 0.5rem 0;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--gradient-primary);
            transition: width 0.3s ease;
        }

        .nav-link:hover::after {
            width: 100%;
        }

        .nav-link:hover {
            color: var(--accent-primary);
            transform: translateY(-2px);
        }

        .nav-link.active {
            color: var(--accent-primary);
        }

        .nav-link.active::after {
            width: 100%;
        }

        .nav-logo {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-logo-icon {
            width: 40px;
            height: 40px;
            background: var(--gradient-primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
        }

        .nav-login {
            background: var(--gradient-primary);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: var(--shadow);
        }

        .nav-login:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        /* Hero Section */
        .hero-section {
            min-height: 60vh;
            background: var(--bg-primary);
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
            padding-top: 80px;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 20%, var(--accent-primary) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, var(--accent-secondary) 0%, transparent 50%);
            opacity: 0.1;
            animation: floatGradient 20s ease-in-out infinite;
        }

        @keyframes floatGradient {
            0%, 100% { transform: translate(0, 0) scale(1); }
            25% { transform: translate(-20px, -20px) scale(1.1); }
            50% { transform: translate(20px, -10px) scale(0.9); }
            75% { transform: translate(-10px, 20px) scale(1.05); }
        }

        .hero-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            text-align: center;
            position: relative;
            z-index: 2;
        }

        .hero-title {
            font-family: 'Playfair Display', serif;
            font-size: 3.5rem;
            font-weight: 900;
            margin-bottom: 1.5rem;
            color: var(--text-primary);
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-subtitle {
            font-size: 1.25rem;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto;
        }

        /* Contact Section */
        .contact-section {
            padding: 6rem 2rem;
            background: var(--bg-primary);
            position: relative;
            overflow: hidden;
        }

        .contact-section:nth-child(even) {
            background: var(--bg-tertiary);
        }

        .contact-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 900;
            margin-bottom: 1rem;
            color: var(--text-primary);
            text-align: center;
        }

        .section-subtitle {
            font-size: 1.1rem;
            color: var(--text-secondary);
            text-align: center;
            max-width: 600px;
            margin: 0 auto 3rem;
        }

        /* Contact Grid */
        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            margin-top: 3rem;
        }

        /* Contact Form */
        .contact-form {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: var(--shadow-lg);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-primary);
        }

        .form-input,
        .form-textarea {
            width: 100%;
            padding: 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: var(--bg-primary);
            color: var(--text-primary);
            font-family: inherit;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-input:focus,
        .form-textarea:focus {
            outline: none;
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-textarea {
            resize: vertical;
            min-height: 120px;
        }

        .form-select {
            width: 100%;
            padding: 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: var(--bg-primary);
            color: var(--text-primary);
            font-family: inherit;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-select:focus {
            outline: none;
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .submit-button {
            width: 100%;
            padding: 1rem 2rem;
            background: var(--gradient-primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: var(--shadow);
        }

        .submit-button:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        /* Contact Info */
        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .info-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 2rem;
            transition: all 0.3s ease;
        }

        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .info-icon {
            width: 60px;
            height: 60px;
            background: var(--gradient-primary);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 1.5rem;
        }

        .info-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .info-content {
            color: var(--text-secondary);
            line-height: 1.6;
        }

        .info-link {
            color: var(--accent-primary);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .info-link:hover {
            color: var(--accent-secondary);
            text-decoration: underline;
        }

        /* Map Section */
        .map-section {
            padding: 6rem 2rem;
            background: var(--bg-tertiary);
        }

        .map-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .map-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: var(--shadow-lg);
        }

        .map-placeholder {
            width: 100%;
            height: 400px;
            background: var(--bg-tertiary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            font-size: 1.1rem;
            border: 2px dashed var(--border-color);
        }

        /* Social Section */
        .social-section {
            padding: 4rem 2rem;
            background: var(--gradient-primary);
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .social-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 50%, rgba(255,255,255,0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 50%, rgba(255,255,255,0.08) 0%, transparent 50%);
            animation: floatGradient 15s ease-in-out infinite;
        }

        .social-container {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }

        .social-title {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 900;
            margin-bottom: 1rem;
        }

        .social-subtitle {
            font-size: 1.1rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .social-links {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .social-link {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .social-link:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-3px);
        }

        /* Theme Switcher */
        .theme-switcher {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 1001;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 50px;
            padding: 0.5rem;
            box-shadow: var(--shadow-lg);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .theme-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: var(--transition);
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }

        .theme-btn:hover {
            transform: scale(1.1);
            background: var(--accent-primary);
            color: white;
        }

        .theme-btn.active {
            background: var(--accent-primary);
            color: white;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }

            .section-title {
                font-size: 2rem;
            }

            .nav-menu {
                gap: 1rem;
            }

            .nav-link {
                font-size: 0.85rem;
                padding: 0.25rem 0;
            }

            .nav-login {
                padding: 0.5rem 1rem;
                font-size: 0.85rem;
            }

            .contact-section {
                padding: 4rem 1.5rem;
            }

            .contact-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .map-placeholder {
                height: 300px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="nav-header" id="navHeader">
        <div class="nav-content">
            <a href="index.php" class="nav-logo">
                <div class="nav-logo-icon">🍽️</div>
                Frank Restaurant
            </a>
            <div class="nav-menu">
                <a href="about.php" class="nav-link">About</a>
                <a href="services.php" class="nav-link">Services</a>
                <a href="contact.php" class="nav-link active">Contact</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-container">
            <h1 class="hero-title">Get in Touch</h1>
            <p class="hero-subtitle">
                We're here to help you transform your restaurant business. Reach out to us for questions, demos, or support.
            </p>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact-section">
        <div class="contact-container">
            <h2 class="section-title">Contact Us</h2>
            <p class="section-subtitle">
                Fill out the form below and we'll get back to you within 24 hours
            </p>
            
            <div class="contact-grid">
                <!-- Contact Form -->
                <div class="contact-form">
                    <form action="#" method="POST">
                        <div class="form-group">
                            <label class="form-label" for="name">Full Name *</label>
                            <input type="text" id="name" name="name" class="form-input" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="email">Email Address *</label>
                            <input type="email" id="email" name="email" class="form-input" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" class="form-input">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="restaurant">Restaurant Name</label>
                            <input type="text" id="restaurant" name="restaurant" class="form-input">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="subject">Subject *</label>
                            <select id="subject" name="subject" class="form-select" required>
                                <option value="">Select a subject</option>
                                <option value="demo">Request a Demo</option>
                                <option value="pricing">Pricing Information</option>
                                <option value="support">Technical Support</option>
                                <option value="sales">Sales Inquiry</option>
                                <option value="partnership">Partnership Opportunity</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="message">Message *</label>
                            <textarea id="message" name="message" class="form-textarea" required></textarea>
                        </div>

                        <button type="submit" class="submit-button">Send Message</button>
                    </form>
                </div>

                <!-- Contact Information -->
                <div class="contact-info">
                    <div class="info-card">
                        <div class="info-icon">📍</div>
                        <h3 class="info-title">Office Location</h3>
                        <div class="info-content">
                            <p>Near Seait, National Highway<br>
                            Tupi, South Cotabato<br>
                            Philippines 9505</p>
                            <a href="#" class="info-link">Get Directions</a>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="info-icon">📞</div>
                        <h3 class="info-title">Phone Support</h3>
                        <div class="info-content">
                            <p>Main: <a href="tel:+63288881234" class="info-link">+63 2 8888 1234</a></p>
                            <p>Mobile: <a href="tel:+639177654321" class="info-link">+63 917 765 4321</a></p>
                            <p>Hotline: <a href="tel:+63288885678" class="info-link">+63 2 8888 5678</a></p>
                            <p>Available: Monday - Friday, 9AM - 6PM</p>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="info-icon">✉️</div>
                        <h3 class="info-title">Email Support</h3>
                        <div class="info-content">
                            <p>General: <a href="mailto:info@frankrestaurant.com" class="info-link">info@frankrestaurant.com</a></p>
                            <p>Support: <a href="mailto:support@frankrestaurant.com" class="info-link">support@frankrestaurant.com</a></p>
                            <p>Sales: <a href="mailto:sales@frankrestaurant.com" class="info-link">sales@frankrestaurant.com</a></p>
                            <p>Response time: Within 24 hours</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="map-section">
        <div class="map-container">
            <h2 class="section-title">Visit Our Office</h2>
            <p class="section-subtitle">
                Come visit us for a personal demo and consultation
            </p>
            
            <div class="map-card">
                <div class="map-placeholder">
                    🗺️ Interactive Map Loading...
                </div>
            </div>
        </div>
    </section>

    <!-- Social Section -->
    <section class="social-section">
        <div class="social-container">
            <h2 class="social-title">Connect With Us</h2>
            <p class="social-subtitle">
                Follow us on social media for updates, tips, and industry insights
            </p>
            
            <div class="social-links">
                <a href="#" class="social-link" title="Facebook">📘</a>
                <a href="#" class="social-link" title="Twitter">🐦</a>
                <a href="#" class="social-link" title="LinkedIn">💼</a>
                <a href="#" class="social-link" title="Instagram">📷</a>
                <a href="#" class="social-link" title="YouTube">📺</a>
                <a href="#" class="social-link" title="WhatsApp">💬</a>
            </div>
        </div>
    </section>

    <!-- Theme Switcher -->
    <div class="theme-switcher">
        <button class="theme-btn active" id="lightTheme" title="Light Theme">☀️</button>
        <button class="theme-btn" id="darkTheme" title="Dark Theme">🌙</button>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Navigation scroll effect
            window.addEventListener('scroll', function() {
                const nav = document.getElementById('navHeader');
                if (window.scrollY > 50) {
                    nav.classList.add('scrolled');
                } else {
                    nav.classList.remove('scrolled');
                }
            });

            // Form submission
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Get form data
                const formData = new FormData(form);
                const data = Object.fromEntries(formData);
                
                // Simple validation
                if (!data.name || !data.email || !data.subject || !data.message) {
                    alert('Please fill in all required fields');
                    return;
                }
                
                // Email validation
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(data.email)) {
                    alert('Please enter a valid email address');
                    return;
                }
                
                // Show success message (in real implementation, this would send to server)
                alert('Thank you for your message! We will get back to you within 24 hours.');
                form.reset();
            });

            // Theme Switcher
            const lightThemeBtn = document.getElementById('lightTheme');
            const darkThemeBtn = document.getElementById('darkTheme');
            const html = document.documentElement;

            // Load saved theme or default to light
            const savedTheme = localStorage.getItem('theme') || 'light';
            if (savedTheme === 'dark') {
                html.setAttribute('data-theme', 'dark');
                darkThemeBtn.classList.add('active');
                lightThemeBtn.classList.remove('active');
            }

            lightThemeBtn.addEventListener('click', () => {
                html.setAttribute('data-theme', 'light');
                localStorage.setItem('theme', 'light');
                lightThemeBtn.classList.add('active');
                darkThemeBtn.classList.remove('active');
            });

            darkThemeBtn.addEventListener('click', () => {
                html.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
                darkThemeBtn.classList.add('active');
                lightThemeBtn.classList.remove('active');
            });
        });
    </script>
</body>
</html>
