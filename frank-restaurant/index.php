<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Frank Restaurant Management System - Complete Restaurant Solution</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Theme Switcher Styles */
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
    </style>
    <style>
        /* Professional Landing Page Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            overflow-x: hidden;
        }

        /* Navigation */
        .nav-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: var(--bg-card);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .nav-header.scrolled {
            background: var(--bg-card);
            box-shadow: 0 2px 20px rgba(0,0,0,0.3);
        }

        .nav-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem 2rem;
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
            min-height: 100vh;
            background: var(--bg-primary);
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
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
                radial-gradient(circle at 80% 80%, var(--accent-secondary) 0%, transparent 50%),
                radial-gradient(circle at 50% 50%, var(--bg-tertiary) 0%, transparent 70%);
            opacity: 0.1;
            animation: floatGradient 20s ease-in-out infinite;
        }

        .hero-section::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                radial-gradient(circle at 25% 25%, var(--accent-primary) 0%, transparent 2px),
                radial-gradient(circle at 75% 75%, var(--accent-secondary) 0%, transparent 2px);
            background-size: 60px 60px, 80px 80px;
            background-position: 0 0, 30px 30px;
            opacity: 0.03;
            animation: patternMove 30s linear infinite;
        }

        .hero-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }

        .hero-content {
            animation: fadeInUp 1s ease;
            position: relative;
            z-index: 2;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--gradient-primary);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }

        .hero-title {
            font-family: 'Playfair Display', serif;
            font-size: 3.5rem;
            font-weight: 900;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            color: var(--text-primary);
        }

        .hero-subtitle {
            font-size: 1.25rem;
            line-height: 1.6;
            color: var(--text-secondary);
            margin-bottom: 2.5rem;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: white;
            padding: 1rem 2rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: var(--shadow);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-secondary {
            background: transparent;
            color: var(--accent-primary);
            padding: 1rem 2rem;
            border: 2px solid var(--accent-primary);
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: var(--accent-primary);
            color: white;
        }

        .hero-visual {
            position: relative;
            animation: fadeInRight 1s ease;
            z-index: 2;
        }

        .hero-dashboard {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }

        .dashboard-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .dashboard-logo {
            width: 40px;
            height: 40px;
            background: var(--gradient-primary);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .dashboard-title {
            font-weight: 700;
            color: var(--text-primary);
        }

        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--bg-tertiary);
            padding: 1rem;
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--accent-primary);
            display: block;
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .dashboard-chart {
            background: var(--bg-tertiary);
            height: 120px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        /* Features Section */
        .features-section {
            padding: 6rem 2rem;
            background: var(--bg-primary);
            position: relative;
            overflow: hidden;
        }

        .features-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                linear-gradient(135deg, var(--accent-primary) 0%, transparent 70%),
                linear-gradient(225deg, var(--accent-secondary) 0%, transparent 70%);
            opacity: 0.02;
            animation: shimmer 15s ease-in-out infinite;
        }

        .features-container {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }

        .section-header {
            text-align: center;
            margin-bottom: 4rem;
            position: relative;
            z-index: 2;
        }

        .section-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--gradient-primary);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 3rem;
            font-weight: 900;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .section-subtitle {
            font-size: 1.25rem;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 2rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--accent-primary);
        }

        .feature-icon {
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

        .feature-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .feature-description {
            color: var(--text-secondary);
            line-height: 1.6;
        }

        /* System Modules Section */
        .modules-section {
            padding: 6rem 2rem;
            background: var(--bg-tertiary);
            position: relative;
            overflow: hidden;
        }

        .modules-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 30% 70%, var(--accent-primary) 0%, transparent 40%),
                radial-gradient(circle at 70% 30%, var(--accent-secondary) 0%, transparent 40%);
            opacity: 0.05;
            animation: floatGradient 25s ease-in-out infinite reverse;
        }

        .modules-section::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                radial-gradient(circle at 20% 80%, var(--accent-primary) 0%, transparent 1px),
                radial-gradient(circle at 80% 20%, var(--accent-secondary) 0%, transparent 1px),
                radial-gradient(circle at 50% 50%, var(--bg-primary) 0%, transparent 2px);
            background-size: 100px 100px, 120px 120px, 60px 60px;
            background-position: 0 0, 40px 40px, 20px 20px;
            opacity: 0.02;
            animation: patternMove 40s linear infinite reverse;
        }

        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }

        .module-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .module-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .module-icon {
            width: 80px;
            height: 80px;
            background: var(--gradient-primary);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            margin: 0 auto 1.5rem;
        }

        .module-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .module-features {
            list-style: none;
            text-align: left;
        }

        .module-features li {
            padding: 0.5rem 0;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .module-features li::before {
            content: '✓';
            color: var(--accent-primary);
            font-weight: 700;
        }

        /* CTA Section */
        .cta-section {
            padding: 6rem 2rem;
            background: var(--gradient-primary);
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .cta-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 50%, rgba(255,255,255,0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 50%, rgba(255,255,255,0.08) 0%, transparent 50%),
                radial-gradient(circle at 50% 20%, rgba(255,255,255,0.05) 0%, transparent 60%);
            animation: floatGradient 15s ease-in-out infinite;
        }

        .cta-section::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                radial-gradient(circle at 25% 25%, rgba(255,255,255,0.1) 0%, transparent 2px),
                radial-gradient(circle at 75% 75%, rgba(255,255,255,0.08) 0%, transparent 2px);
            background-size: 80px 80px, 100px 100px;
            background-position: 0 0, 40px 40px;
            animation: patternMove 25s linear infinite;
        }

        .cta-content {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }

        .cta-title {
            font-family: 'Playfair Display', serif;
            font-size: 3rem;
            font-weight: 900;
            margin-bottom: 1.5rem;
        }

        .cta-description {
            font-size: 1.25rem;
            margin-bottom: 2.5rem;
            opacity: 0.9;
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-cta {
            padding: 1rem 2rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 1.1rem;
        }

        .btn-cta-white {
            background: white;
            color: var(--accent-primary);
        }

        .btn-cta-white:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .btn-cta-outline {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        .btn-cta-outline:hover {
            background: white;
            color: var(--accent-primary);
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes floatGradient {
            0%, 100% {
                transform: translate(0, 0) scale(1);
            }
            25% {
                transform: translate(-20px, -20px) scale(1.1);
            }
            50% {
                transform: translate(20px, -10px) scale(0.9);
            }
            75% {
                transform: translate(-10px, 20px) scale(1.05);
            }
        }

        @keyframes shimmer {
            0%, 100% {
                opacity: 0.02;
                transform: translateX(0) translateY(0);
            }
            50% {
                opacity: 0.05;
                transform: translateX(20px) translateY(-10px);
            }
        }

        @keyframes patternMove {
            0% {
                background-position: 0 0, 30px 30px;
            }
            100% {
                background-position: 100px 100px, 130px 130px;
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-container {
                grid-template-columns: 1fr;
                gap: 2rem;
                text-align: center;
            }

            .hero-title {
                font-size: 2.5rem;
            }

            .section-title {
                font-size: 2rem;
            }

            .cta-title {
                font-size: 2rem;
            }

            .features-grid,
            .modules-grid {
                grid-template-columns: 1fr;
            }

            .nav-content {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="nav-header" id="navHeader">
        <div class="nav-content">
            <a href="#" class="nav-logo">
                <div class="nav-logo-icon">🍽️</div>
                Frank Restaurant
            </a>
            <a href="login.php" class="nav-login">
                Staff Login
            </a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-container">
            <div class="hero-content">
                <div class="hero-badge">
                    <span>🚀</span>
                    Complete Restaurant Management Solution
                </div>
                <h1 class="hero-title">Streamline Your Restaurant Operations with Smart Management</h1>
                <p class="hero-subtitle">
                    Powerful reservation system, real-time table management, and comprehensive order tracking - all in one elegant platform designed for modern restaurants.
                </p>
                <div class="hero-buttons">
                    <a href="#features" class="btn-primary">Explore Features</a>
                    <a href="register.php" class="btn-secondary">Get Started</a>
                </div>
            </div>
            <div class="hero-visual">
                <div class="hero-dashboard">
                    <div class="dashboard-header">
                        <div class="dashboard-logo">📊</div>
                        <div class="dashboard-title">Dashboard Overview</div>
                    </div>
                    <div class="dashboard-stats">
                        <div class="stat-card">
                            <span class="stat-number">24</span>
                            <span class="stat-label">Active Tables</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number">156</span>
                            <span class="stat-label">Today's Orders</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number">89%</span>
                            <span class="stat-label">Table Occupancy</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number">₱24.5K</span>
                            <span class="stat-label">Revenue</span>
                        </div>
                    </div>
                    <div class="dashboard-chart">
                        📈 Real-time Analytics Dashboard
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section" id="features">
        <div class="features-container">
            <div class="section-header">
                <div class="section-badge">
                    <span>⭐</span>
                    Powerful Features
                </div>
                <h2 class="section-title">Everything You Need to Manage Your Restaurant</h2>
                <p class="section-subtitle">
                    Our comprehensive system handles everything from reservations to billing, giving you more time to focus on what matters - your customers.
                </p>
            </div>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">📅</div>
                    <h3 class="feature-title">Smart Reservations</h3>
                    <p class="feature-description">
                        Advanced booking system with automatic table assignment, guest preferences, and real-time availability tracking.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🪑</div>
                    <h3 class="feature-title">Table Management</h3>
                    <p class="feature-description">
                        Visual floor plan with real-time status updates, capacity management, and intelligent seating arrangements.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🧾</div>
                    <h3 class="feature-title">Order Processing</h3>
                    <p class="feature-description">
                        Streamlined order management with pre-ordering, real-time kitchen updates, and integrated billing system.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">👥</div>
                    <h3 class="feature-title">Customer Management</h3>
                    <p class="feature-description">
                        Complete guest profiles with loyalty tracking, preferences, dining history, and VIP status management.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">📈</div>
                    <h3 class="feature-title">Analytics & Reports</h3>
                    <p class="feature-description">
                        Comprehensive insights with revenue tracking, occupancy rates, popular items, and performance metrics.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🔐</div>
                    <h3 class="feature-title">Role-Based Access</h3>
                    <p class="feature-description">
                        Secure multi-level access control for admin, manager, staff, and customers with appropriate permissions.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- System Modules Section -->
    <section class="modules-section">
        <div class="features-container">
            <div class="section-header">
                <div class="section-badge">
                    <span>🎯</span>
                    System Modules
                </div>
                <h2 class="section-title">Complete Management Ecosystem</h2>
                <p class="section-subtitle">
                    Each module is designed to work seamlessly together, providing a complete solution for restaurant operations.
                </p>
            </div>

            <div class="modules-grid">
                <div class="module-card">
                    <div class="module-icon">🏢</div>
                    <h3 class="module-title">Dashboard</h3>
                    <ul class="module-features">
                        <li>Real-time statistics</li>
                        <li>Today's overview</li>
                        <li>Quick actions</li>
                        <li>Performance metrics</li>
                    </ul>
                </div>

                <div class="module-card">
                    <div class="module-icon">📋</div>
                    <h3 class="module-title">Reservations</h3>
                    <ul class="module-features">
                        <li>Online booking</li>
                        <li>Table assignment</li>
                        <li>Guest details</li>
                        <li>Status tracking</li>
                    </ul>
                </div>

                <div class="module-card">
                    <div class="module-icon">🍽️</div>
                    <h3 class="module-title">Orders</h3>
                    <ul class="module-features">
                        <li>Pre-ordering</li>
                        <li>Menu management</li>
                        <li>Billing integration</li>
                        <li>Order tracking</li>
                    </ul>
                </div>

                <div class="module-card">
                    <div class="module-icon">🪑</div>
                    <h3 class="module-title">Tables</h3>
                    <ul class="module-features">
                        <li>Floor plan view</li>
                        <li>Status management</li>
                        <li>Capacity tracking</li>
                        <li>Guest assignment</li>
                    </ul>
                </div>

                <div class="module-card">
                    <div class="module-icon">👥</div>
                    <h3 class="module-title">Customers</h3>
                    <ul class="module-features">
                        <li>Guest profiles</li>
                        <li>Loyalty points</li>
                        <li>Preferences</li>
                        <li>History tracking</li>
                    </ul>
                </div>

                <div class="module-card">
                    <div class="module-icon">📊</div>
                    <h3 class="module-title">Reports</h3>
                    <ul class="module-features">
                        <li>Revenue analytics</li>
                        <li>Occupancy reports</li>
                        <li>Popular items</li>
                        <li>Performance insights</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="cta-content">
            <h2 class="cta-title">Ready to Transform Your Restaurant?</h2>
            <p class="cta-description">
                Join hundreds of restaurants using our management system to streamline operations, increase efficiency, and deliver exceptional dining experiences.
            </p>
            <div class="cta-buttons">
                <a href="register.php" class="btn-cta btn-cta-white">Start Free Trial</a>
                <a href="login.php" class="btn-cta btn-cta-outline">Staff Login</a>
            </div>
        </div>
    </section>

    <!-- Theme Switcher -->
    <div class="theme-switcher">
        <button class="theme-btn active" id="lightTheme" title="Light Theme">☀️</button>
        <button class="theme-btn" id="darkTheme" title="Dark Theme">🌙</button>
    </div>

    <script>
        // Navigation scroll effect
        window.addEventListener('scroll', function() {
            const nav = document.getElementById('navHeader');
            if (window.scrollY > 50) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }
        });

        // Smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Intersection Observer for animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animation = 'fadeInUp 0.6s ease forwards';
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        document.querySelectorAll('.feature-card, .module-card').forEach(el => {
            observer.observe(el);
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
    </script>
</body>
</html>
