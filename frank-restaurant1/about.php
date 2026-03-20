<?php
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Frank Restaurant | Our Story & Mission</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@400;700;800;900&family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* About Page Styles */
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

        /* Content Sections */
        .content-section {
            padding: 6rem 2rem;
            background: var(--bg-primary);
            position: relative;
            overflow: hidden;
        }

        .content-section:nth-child(even) {
            background: var(--bg-tertiary);
        }

        .content-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 900;
            margin-bottom: 2rem;
            color: var(--text-primary);
            text-align: center;
        }

        .section-content {
            font-size: 1.1rem;
            color: var(--text-secondary);
            line-height: 1.8;
            margin-bottom: 2rem;
        }

        /* Story Cards */
        .story-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .story-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 2rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .story-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }

        .story-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .story-icon {
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

        .story-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .story-description {
            color: var(--text-secondary);
            line-height: 1.6;
        }

        /* Team Section */
        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .team-member {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .team-member:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .team-avatar {
            width: 100px;
            height: 100px;
            background: var(--gradient-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: white;
            margin: 0 auto 1.5rem;
        }

        .team-name {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .team-role {
            color: var(--accent-primary);
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .team-bio {
            color: var(--text-secondary);
            font-size: 0.95rem;
            line-height: 1.6;
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

            .content-section {
                padding: 4rem 1.5rem;
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
                <a href="about.php" class="nav-link active">About</a>
                <a href="services.php" class="nav-link">Services</a>
                <a href="contact.php" class="nav-link">Contact</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-container">
            <h1 class="hero-title">Our Story & Mission</h1>
            <p class="hero-subtitle">
                Transforming restaurant management through innovation, technology, and a passion for exceptional dining experiences.
            </p>
        </div>
    </section>

    <!-- About Section -->
    <section class="content-section">
        <div class="content-container">
            <h2 class="section-title">Who We Are</h2>
            <p class="section-content">
                Frank Restaurant Management System was born from a simple observation: restaurant owners needed better tools to manage their growing businesses. Founded in 2020, we've been dedicated to creating comprehensive solutions that address the unique challenges of the restaurant industry.
            </p>
            <p class="section-content">
                Our team combines decades of restaurant experience with cutting-edge technology expertise, ensuring that our platform not only meets technical standards but also understands the real-world needs of restaurant operators.
            </p>
        </div>
    </section>

    <!-- Story Section -->
    <section class="content-section">
        <div class="content-container">
            <h2 class="section-title">Our Journey</h2>
            <div class="story-grid">
                <div class="story-card">
                    <div class="story-icon">🚀</div>
                    <h3 class="story-title">The Beginning</h3>
                    <p class="story-description">
                        Started as a small project to help local restaurants manage their tables more efficiently. Our first client saw a 40% increase in table turnover within the first month.
                    </p>
                </div>
                <div class="story-card">
                    <div class="story-icon">📈</div>
                    <h3 class="story-title">Rapid Growth</h3>
                    <p class="story-description">
                        Expanded from table management to a complete ecosystem including reservations, orders, customer management, and analytics. Now serving over 500 restaurants nationwide.
                    </p>
                </div>
                <div class="story-card">
                    <div class="story-icon">🌟</div>
                    <h3 class="story-title">Innovation Leader</h3>
                    <p class="story-description">
                        Continuously pushing boundaries with AI-powered insights, mobile-first design, and seamless integrations that make restaurant management effortless.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Mission Section -->
    <section class="content-section">
        <div class="content-container">
            <h2 class="section-title">Our Mission & Values</h2>
            <p class="section-content">
                Our mission is to empower restaurants with technology that simplifies operations, enhances customer experiences, and drives sustainable growth. We believe that great restaurant management should be intuitive, powerful, and accessible to all.
            </p>
            <div class="story-grid">
                <div class="story-card">
                    <div class="story-icon">💡</div>
                    <h3 class="story-title">Innovation</h3>
                    <p class="story-description">
                        Constantly evolving our platform with cutting-edge features and technologies that solve real restaurant challenges.
                    </p>
                </div>
                <div class="story-card">
                    <div class="story-icon">🤝</div>
                    <h3 class="story-title">Partnership</h3>
                    <p class="story-description">
                        We work alongside our clients as partners, understanding their needs and helping them succeed in a competitive market.
                    </p>
                </div>
                <div class="story-card">
                    <div class="story-icon">🎯</div>
                    <h3 class="story-title">Excellence</h3>
                    <p class="story-description">
                        Committed to delivering the highest quality solutions that exceed expectations and drive measurable results.
                    </p>
                </div>
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
