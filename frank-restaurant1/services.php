<?php
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services | Frank Restaurant Management Solutions</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@400;700;800;900&family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Services Page Styles */
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

        /* Services Section */
        .services-section {
            padding: 6rem 2rem;
            background: var(--bg-primary);
            position: relative;
            overflow: hidden;
        }

        .services-section:nth-child(even) {
            background: var(--bg-tertiary);
        }

        .services-container {
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

        /* Service Cards */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .service-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 2.5rem;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }

        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
            transform: scaleX(0);
            transition: transform 0.4s ease;
        }

        .service-card:hover::before {
            transform: scaleX(1);
        }

        .service-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-lg);
            border-color: var(--accent-primary);
        }

        .service-icon {
            width: 80px;
            height: 80px;
            background: var(--gradient-primary);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
        }

        .service-card:hover .service-icon {
            transform: scale(1.1) rotate(5deg);
        }

        .service-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .service-description {
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .service-features {
            list-style: none;
        }

        .service-features li {
            padding: 0.5rem 0;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .service-features li::before {
            content: '✓';
            color: var(--accent-primary);
            font-weight: 700;
        }

        /* Pricing Section */
        .pricing-section {
            padding: 6rem 2rem;
            background: var(--gradient-primary);
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .pricing-section::before {
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

        .pricing-container {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }

        .pricing-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 900;
            margin-bottom: 1rem;
        }

        .pricing-subtitle {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .cta-button {
            background: white;
            color: var(--accent-primary);
            padding: 1rem 2rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-block;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .cta-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
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

            .services-section {
                padding: 4rem 1.5rem;
            }

            .services-grid {
                grid-template-columns: 1fr;
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
                <a href="services.php" class="nav-link active">Services</a>
                <a href="contact.php" class="nav-link">Contact</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-container">
            <h1 class="hero-title">Comprehensive Restaurant Solutions</h1>
            <p class="hero-subtitle">
                End-to-end management services designed to streamline operations, boost efficiency, and enhance customer experiences.
            </p>
        </div>
    </section>

    <!-- Core Services Section -->
    <section class="services-section">
        <div class="services-container">
            <h2 class="section-title">Core Management Services</h2>
            <p class="section-subtitle">
                Essential services that form the foundation of modern restaurant management
            </p>
            
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-icon">📅</div>
                    <h3 class="service-title">Reservation Management</h3>
                    <p class="service-description">
                        Complete booking system with real-time availability, automated confirmations, and guest preference tracking.
                    </p>
                    <ul class="service-features">
                        <li>Online booking portal</li>
                        <li>Automated table assignment</li>
                        <li>Guest history & preferences</li>
                        <li>Waitlist management</li>
                        <li>SMS & email notifications</li>
                    </ul>
                </div>

                <div class="service-card">
                    <div class="service-icon">🪑</div>
                    <h3 class="service-title">Table & Floor Management</h3>
                    <p class="service-description">
                        Visual floor planning with real-time status tracking and intelligent seating optimization.
                    </p>
                    <ul class="service-features">
                        <li>Digital floor plan designer</li>
                        <li>Real-time table status</li>
                        <li>Automated seating suggestions</li>
                        <li>Turn time optimization</li>
                        <li>Capacity management</li>
                    </ul>
                </div>

                <div class="service-card">
                    <div class="service-icon">🧾</div>
                    <h3 class="service-title">Order & Menu Management</h3>
                    <p class="service-description">
                        Streamlined ordering system with digital menus, kitchen display integration, and order tracking.
                    </p>
                    <ul class="service-features">
                        <li>Digital menu management</li>
                        <li>Real-time kitchen updates</li>
                        <li>Order tracking system</li>
                        <li>Special dietary accommodations</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Advanced Services Section -->
    <section class="services-section">
        <div class="services-container">
            <h2 class="section-title">Advanced Business Services</h2>
            <p class="section-subtitle">
                Premium services to scale your restaurant business and drive growth
            </p>
            
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-icon">👥</div>
                    <h3 class="service-title">Customer Relationship Management</h3>
                    <p class="service-description">
                        Complete guest profiles with loyalty programs, marketing automation, and personalized experiences.
                    </p>
                    <ul class="service-features">
                        <li>Guest profile database</li>
                        <li>Loyalty program management</li>
                        <li>Targeted marketing campaigns</li>
                        <li>Feedback collection system</li>
                        <li>Personalized promotions</li>
                    </ul>
                </div>

                <div class="service-card">
                    <div class="service-icon">📊</div>
                    <h3 class="service-title">Analytics & Business Intelligence</h3>
                    <p class="service-description">
                        Deep insights with predictive analytics, performance metrics, and strategic planning tools.
                    </p>
                    <ul class="service-features">
                        <li>Real-time dashboards</li>
                        <li>Revenue analytics</li>
                        <li>Customer behavior insights</li>
                        <li>Menu performance analysis</li>
                        <li>Growth forecasting</li>
                    </ul>
                </div>

                <div class="service-card">
                    <div class="service-icon">💳</div>
                    <h3 class="service-title">Payment & Financial Services</h3>
                    <p class="service-description">
                        Integrated payment processing, financial reporting, and comprehensive billing solutions.
                    </p>
                    <ul class="service-features">
                        <li>Multi-payment method support</li>
                        <li>Automated billing & invoicing</li>
                        <li>Financial reporting</li>
                        <li>Tip management</li>
                        <li>Split payment options</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Support Services Section -->
    <section class="services-section">
        <div class="services-container">
            <h2 class="section-title">Support & Implementation Services</h2>
            <p class="section-subtitle">
                Professional services to ensure smooth implementation and ongoing success
            </p>
            
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-icon">🚀</div>
                    <h3 class="service-title">Implementation & Onboarding</h3>
                    <p class="service-description">
                        Expert-led setup, data migration, staff training, and go-live support for seamless transition.
                    </p>
                    <ul class="service-features">
                        <li>System configuration</li>
                        <li>Data migration services</li>
                        <li>Staff training programs</li>
                        <li>Go-live support</li>
                        <li>Process optimization</li>
                    </ul>
                </div>

                <div class="service-card">
                    <div class="service-icon">🛠️</div>
                    <h3 class="service-title">Technical Support & Maintenance</h3>
                    <p class="service-description">
                        24/7 technical support, regular updates, and proactive system maintenance.
                    </p>
                    <ul class="service-features">
                        <li>24/7 help desk support</li>
                        <li>Regular system updates</li>
                        <li>Performance monitoring</li>
                        <li>Security maintenance</li>
                        <li>Backup & recovery</li>
                    </ul>
                </div>

                <div class="service-card">
                    <div class="service-icon">🎓</div>
                    <h3 class="service-title">Training & Consulting</h3>
                    <p class="service-description">
                        Ongoing education, best practices, and strategic consulting to maximize your investment.
                    </p>
                    <ul class="service-features">
                        <li>Staff certification programs</li>
                        <li>Best practices consulting</li>
                        <li>Process optimization</li>
                        <li>Advanced feature training</li>
                        <li>Industry expertise sharing</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="pricing-section">
        <div class="pricing-container">
            <h2 class="pricing-title">Ready to Transform Your Restaurant?</h2>
            <p class="pricing-subtitle">
                Get started with our comprehensive services and see immediate results in your operations
            </p>
            <a href="contact.php" class="cta-button">Get Started Today</a>
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
