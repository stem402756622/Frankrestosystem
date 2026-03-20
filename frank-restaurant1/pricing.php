<?php
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pricing Plans | Frank Restaurant Management System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@400;700;800;900&family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Pricing Page Styles */
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

        /* Pricing Section */
        .pricing-section {
            padding: 6rem 2rem;
            background: var(--bg-primary);
            position: relative;
            overflow: hidden;
        }

        .pricing-section:nth-child(even) {
            background: var(--bg-tertiary);
        }

        .pricing-container {
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

        /* Pricing Cards */
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .pricing-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 2.5rem;
            text-align: center;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }

        .pricing-card.featured {
            border: 2px solid var(--accent-primary);
            transform: scale(1.05);
            box-shadow: var(--shadow-lg);
        }

        .pricing-card.featured::before {
            content: 'MOST POPULAR';
            position: absolute;
            top: 15px;
            right: -30px;
            background: var(--accent-primary);
            color: white;
            padding: 0.5rem 3rem;
            font-size: 0.75rem;
            font-weight: 700;
            transform: rotate(45deg);
        }

        .pricing-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-lg);
        }

        .pricing-card.featured:hover {
            transform: scale(1.05) translateY(-10px);
        }

        .pricing-tier {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }

        .pricing-price {
            font-size: 3rem;
            font-weight: 900;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .pricing-period {
            color: var(--text-secondary);
            font-size: 1rem;
            margin-bottom: 2rem;
        }

        .pricing-features {
            list-style: none;
            margin-bottom: 2rem;
            text-align: left;
        }

        .pricing-features li {
            padding: 0.75rem 0;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-bottom: 1px solid var(--border-color);
        }

        .pricing-features li:last-child {
            border-bottom: none;
        }

        .pricing-features li::before {
            content: '✓';
            color: var(--accent-primary);
            font-weight: 700;
            font-size: 1.1rem;
        }

        .pricing-button {
            width: 100%;
            padding: 1rem 2rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 1rem;
            display: block;
        }

        .pricing-button.primary {
            background: var(--gradient-primary);
            color: white;
            box-shadow: var(--shadow);
        }

        .pricing-button.primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .pricing-button.secondary {
            background: transparent;
            color: var(--accent-primary);
            border: 2px solid var(--accent-primary);
        }

        .pricing-button.secondary:hover {
            background: var(--accent-primary);
            color: white;
        }

        /* FAQ Section */
        .faq-section {
            padding: 6rem 2rem;
            background: var(--bg-tertiary);
        }

        .faq-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .faq-item {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            margin-bottom: 1rem;
            overflow: hidden;
        }

        .faq-question {
            padding: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .faq-question:hover {
            background: var(--bg-tertiary);
        }

        .faq-question::after {
            content: '+';
            font-size: 1.5rem;
            color: var(--accent-primary);
            transition: transform 0.3s ease;
        }

        .faq-item.active .faq-question::after {
            transform: rotate(45deg);
        }

        .faq-answer {
            padding: 0 1.5rem;
            max-height: 0;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .faq-item.active .faq-answer {
            padding: 0 1.5rem 1.5rem;
            max-height: 200px;
        }

        .faq-answer p {
            color: var(--text-secondary);
            line-height: 1.6;
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
                radial-gradient(circle at 80% 50%, rgba(255,255,255,0.08) 0%, transparent 50%);
            animation: floatGradient 15s ease-in-out infinite;
        }

        .cta-container {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }

        .cta-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 900;
            margin-bottom: 1rem;
        }

        .cta-subtitle {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
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

        .cta-button-outline {
            background: transparent;
            color: white;
            border: 2px solid white;
            padding: 1rem 2rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .cta-button-outline:hover {
            background: white;
            color: var(--accent-primary);
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

            .pricing-section {
                padding: 4rem 1.5rem;
            }

            .pricing-grid {
                grid-template-columns: 1fr;
            }

            .pricing-card.featured {
                transform: scale(1);
            }

            .pricing-card.featured:hover {
                transform: translateY(-10px);
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
                <a href="pricing.php" class="nav-link active">Pricing</a>
                <a href="contact.php" class="nav-link">Contact</a>
                <a href="login.php" class="nav-login">Staff Login</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-container">
            <h1 class="hero-title">Transparent Pricing Plans</h1>
            <p class="hero-subtitle">
                Choose the perfect plan for your restaurant. No hidden fees, just powerful features that grow with your business.
            </p>
        </div>
    </section>

    <!-- Pricing Section -->
    <section class="pricing-section">
        <div class="pricing-container">
            <h2 class="section-title">Select Your Plan</h2>
            <p class="section-subtitle">
                All plans include core features. Choose based on your restaurant size and needs.
            </p>
            
            <div class="pricing-grid">
                <!-- Starter Plan -->
                <div class="pricing-card">
                    <h3 class="pricing-tier">Starter</h3>
                    <div class="pricing-price">₱2,999</div>
                    <div class="pricing-period">per month</div>
                    <ul class="pricing-features">
                        <li>Up to 20 tables</li>
                        <li>Basic reservation system</li>
                        <li>Simple order management</li>
                        <li>Customer database (500 contacts)</li>
                        <li>Basic analytics</li>
                        <li>Email support</li>
                        <li>Mobile app access</li>
                    </ul>
                    <a href="register.php" class="pricing-button secondary">Start Free Trial</a>
                </div>

                <!-- Professional Plan -->
                <div class="pricing-card featured">
                    <h3 class="pricing-tier">Professional</h3>
                    <div class="pricing-price">₱5,999</div>
                    <div class="pricing-period">per month</div>
                    <ul class="pricing-features">
                        <li>Up to 50 tables</li>
                        <li>Advanced reservation system</li>
                        <li>Complete order management</li>
                        <li>Customer database (2,000 contacts)</li>
                        <li>Advanced analytics & reports</li>
                        <li>Priority support</li>
                        <li>Mobile app access</li>
                        <li>Loyalty program features</li>
                        <li>Online ordering integration</li>
                    </ul>
                    <a href="register.php" class="pricing-button primary">Start Free Trial</a>
                </div>

                <!-- Enterprise Plan -->
                <div class="pricing-card">
                    <h3 class="pricing-tier">Enterprise</h3>
                    <div class="pricing-price">₱9,999</div>
                    <div class="pricing-period">per month</div>
                    <ul class="pricing-features">
                        <li>Unlimited tables</li>
                        <li>Full reservation system</li>
                        <li>Complete order management</li>
                        <li>Unlimited customer database</li>
                        <li>Advanced analytics & AI insights</li>
                        <li>24/7 dedicated support</li>
                        <li>Mobile app access</li>
                        <li>Custom loyalty programs</li>
                        <li>Multi-location support</li>
                        <li>API access</li>
                        <li>Custom integrations</li>
                        <li>White-label options</li>
                    </ul>
                    <a href="contact.php" class="pricing-button secondary">Contact Sales</a>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq-section">
        <div class="faq-container">
            <h2 class="section-title">Frequently Asked Questions</h2>
            
            <div class="faq-item">
                <div class="faq-question">Can I change my plan later?</div>
                <div class="faq-answer">
                    <p>Yes! You can upgrade or downgrade your plan at any time. Changes take effect at the start of your next billing cycle.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">Is there a contract or commitment?</div>
                <div class="faq-answer">
                    <p>No long-term contracts required. You can cancel your subscription at any time with no cancellation fees.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">What payment methods do you accept?</div>
                <div class="faq-answer">
                    <p>We accept credit cards, debit cards, bank transfers, and popular digital payment methods like GCash and PayMaya.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">Do you offer discounts for annual billing?</div>
                <div class="faq-answer">
                    <p>Yes! Annual billing saves you 20% compared to monthly billing. Contact our sales team for more details.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">Is my data secure?</div>
                <div class="faq-answer">
                    <p>Absolutely. We use bank-level encryption, regular security audits, and comply with data protection regulations.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">Do you provide training and onboarding?</div>
                <div class="faq-answer">
                    <p>Yes! Professional and Enterprise plans include comprehensive onboarding. Starter plans get access to our knowledge base and video tutorials.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="cta-container">
            <h2 class="cta-title">Ready to Get Started?</h2>
            <p class="cta-subtitle">
                Join hundreds of restaurants using Frank Restaurant Management System to streamline their operations and boost revenue.
            </p>
            <div class="cta-buttons">
                <a href="register.php" class="cta-button">Start Your Free Trial</a>
                <a href="contact.php" class="cta-button-outline">Schedule a Demo</a>
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

            // FAQ Accordion
            const faqItems = document.querySelectorAll('.faq-item');
            faqItems.forEach(item => {
                const question = item.querySelector('.faq-question');
                question.addEventListener('click', () => {
                    // Close other items
                    faqItems.forEach(otherItem => {
                        if (otherItem !== item && otherItem.classList.contains('active')) {
                            otherItem.classList.remove('active');
                        }
                    });
                    // Toggle current item
                    item.classList.toggle('active');
                });
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
