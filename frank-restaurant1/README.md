# 🍽️ Frank Restaurant Management System v3.0 — Complete Feature Set

A professional, fully-integrated PHP restaurant management system with **30 implemented features** across Reservation, Ordering, and Combined systems.

---

## ✅ Complete Feature Checklist (30/30 Implemented)

### 📅 RESERVATION SYSTEM (10/10)

| # | Feature | Status | Location |
|---|---------|--------|----------|
| 1 | **Table Reservation** | ✅ | `create_reservation.php`, `reservations.php` |
| 2 | **Reservation Rescheduling** | ✅ | `reservations.php` (max 2 reschedules) |
| 3 | **Email Reservation & Cancellation Notifications** | ✅ | `includes/mailer.php`, sent on all status changes |
| 4 | **Special Menu Requests** | ✅ | `create_reservation.php` - dietary requirements field |
| 5 | **Waitlist** | ✅ | `waitlist.php`, `join_waitlist.php` |
| 6 | **Reservation History Tracking** | ✅ | `my_reservations.php` (customer view) |
| 7 | **VIP Priority Table Booking** | ✅ | VIP status check in `create_reservation.php` |
| 8 | **No-Show Detection Alert** | ✅ | `no_show` status in database + reports tracking |
| 9 | **Peak Hour Restriction** | ✅ | `peak_hours.php` + validation in reservation flow |
| 10 | **Reservation Report Analytics** | ✅ | `reports.php` - no-show rates, completion rates |

### 🛒 ORDERING MANAGEMENT SYSTEM (10/10)

| # | Feature | Status | Location |
|---|---------|--------|----------|
| 1 | **Product Catalog** | ✅ | `menu.php` with categories & dietary filters |
| 2 | **Order Status Tracking** | ✅ | `orders.php` - real-time status updates |
| 3 | **Payment via QR Code** | ✅ | `payment.php` - QR code generation |
| 4 | **Saved Favorites / Order History** | ✅ | `favorites.php`, `ajax_favorite.php` |
| 5 | **Promo Codes & Discounts** | ✅ | `promo_codes.php`, `ajax_promo.php` |
| 6 | **Reviews & Ratings** | ✅ | `submit_review.php`, `testimonials.php` |
| 7 | **Menu & Category Management** | ✅ | `admin_menu.php` |
| 8 | **Low Stock Notification** | ✅ | `inventory.php` - alert banner + badge |
| 9 | **Print Receipt & Invoicing** | ✅ | `receipt.php`, `invoice.php` |
| 10 | **Financial & Sales Report Generation** | ✅ | `reports.php` - revenue charts, metrics |

### 🔗 FRANK COMBINED SYSTEM (10/10)

| # | Feature | Status | Location |
|---|---------|--------|----------|
| 1 | **Smart Table Allocation** | ✅ | Auto-assign in `reservations.php` |
| 2 | **Loyalty Rewards Points** | ✅ | `users` table + display in profile |
| 3 | **Queueing** | ✅ | `queue.php`, `queue_status.php` |
| 4 | **Inventory Management** | ✅ | `inventory.php` with transactions |
| 5 | **No-Show Detection Alert** | ✅ | Cross-referenced in reservation data |
| 6 | **Peak Hour Restriction** | ✅ | Dynamic slot limiting based on volume |
| 7 | **Allergies & Dietary Warning** | ✅ | `menu.php` - allergy alerts on ordering |
| 8 | **VIP Priority Table Booking** | ✅ | Loyalty points + order history factor |
| 9 | **Customer Feedback & Ratings Analysis** | ✅ | `feedback_analysis.php` (manager dashboard) |
| 10 | **Suggestions & Recommendations** | ✅ | `menu.php` - based on order history |

---

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
