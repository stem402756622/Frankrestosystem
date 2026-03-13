# 🍽️ Frank Restaurant Management System v2.0

A professional, fully-integrated PHP restaurant management system with 3 themes, animations, reservations, table management, and more.

---

## ✅ Features

### 🎨 Theme System
- **Dark Theme** (default) — Deep blacks with indigo/violet accents
- **Light Theme** — Clean whites with the same accent palette
- **Ocean Theme** — Deep navy with cyan accents
- Switch themes via floating buttons (bottom-right) or keyboard: **Alt+1**, **Alt+2**, **Alt+3**
- Theme persisted in `localStorage`

### 🔐 Authentication & Roles
| Role | Access |
|------|--------|
| Admin | Full access — all pages |
| Manager | Reservations, orders, customers, reports |
| Staff | Reservations, tables, orders |
| Customer | Own reservations, profile |

### 📄 Pages
| File | Description |
|------|-------------|
| `login.php` | Secure login with demo credentials |
| `register.php` | Customer registration with table preference + confetti |
| `index.php` | Role-based dashboard |
| `reservations.php` | Full reservation management |
| `create_reservation.php` | Smart table assignment + interactive floor |
| `tables.php` | Grid/list floor plan with live status |
| `customers.php` | Customer directory + VIP management |
| `orders.php` | Order tracking |
| `reports.php` | Analytics, revenue charts, metrics |
| `profile.php` | Account settings + password change |
| `logout.php` | Secure logout |
| `404.php` | Custom error page |

### 🎭 Animations
- Staggered card entrance animations
- Floating logo animations on auth pages
- Confetti on registration/success
- Glow pulsing on primary CTAs
- Counter animations on statistics
- Smooth theme transitions
- Ripple effects on buttons
- Modal entrance animations

---

## 🚀 Installation

### 1. Requirements
- PHP 7.4+
- MySQL 5.7+ / MariaDB 10.3+
- Apache/Nginx with mod_rewrite enabled

### 2. Database Setup
```bash
mysql -u root -p < database.sql
```
Or import `database.sql` via phpMyAdmin.

### 3. Configuration
Edit `includes/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'frank_restaurant');
```

### 4. Deploy
Place the `frank-restaurant/` folder in your web root (e.g., `/var/www/html/` or `htdocs/`).

> ⚠️ **Check the login page for demo account credentials!**

---

## 🗂️ File Structure
```
frank-restaurant/
├── assets/
│   ├── css/
│   │   └── style.css          # Master stylesheet (3 themes, 60+ animations)
│   └── js/
│       └── main.js            # Theme switcher, animations, confetti, modals
├── includes/
│   ├── config.php             # App config, session, helpers
│   ├── database.php           # MySQLi DB class
│   ├── header.php             # Sidebar + topbar layout
│   └── footer.php             # Theme switcher + footer
├── index.php                  # Dashboard
├── login.php
├── register.php
├── reservations.php
├── create_reservation.php
├── tables.php
├── customers.php
├── orders.php
├── reports.php
├── profile.php
├── logout.php
├── 404.php
├── database.sql               # Full schema + sample data
├── .htaccess                  # Security + URL rules
└── README.md
```

---

## 🔒 Security Features
- Bcrypt password hashing (cost 10)
- MySQLi prepared statements (no SQL injection)
- Session-based authentication
- Role-based access control
- XSS protection via `htmlspecialchars()`
- Input sanitization on all user inputs
- .htaccess blocks direct include access

---

## 🎨 Customization

### Change Restaurant Name
In `includes/config.php`:
```php
define('SITE_NAME', 'Your Restaurant Name');
```

### Change Currency
In `includes/config.php`, add:
```php
define('CURRENCY_SYMBOL', '₱');
define('CURRENCY_CODE', 'PHP');
```

### Add a New Theme
In `assets/css/style.css`, add:
```css
[data-theme="custom"] {
    --bg-primary: ...;
    --accent-primary: ...;
    /* etc */
}
```

Then add a button in `includes/footer.php`.

---

Built with ❤️ — PHP 7.4+ · MySQL · Vanilla JS · No frameworks
