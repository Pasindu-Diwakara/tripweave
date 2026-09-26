<div align="center">

<img src="https://img.shields.io/badge/TripWeave-Travel%20Planner-14919B?style=for-the-badge&logo=map&logoColor=white" alt="TripWeave"/>

# 🗺️ TripWeave — Travel Itinerary Planner

> *Weave your perfect journey, day by day.*

[![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.x-4479A1?style=flat-square&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=flat-square&logo=bootstrap&logoColor=white)](https://getbootstrap.com/)
[![JavaScript](https://img.shields.io/badge/JavaScript-Vanilla-F7DF1E?style=flat-square&logo=javascript&logoColor=black)](https://developer.mozilla.org/en-US/docs/Web/JavaScript)
[![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)](LICENSE)
[![Status](https://img.shields.io/badge/Status-Active-brightgreen?style=flat-square)]()

<br/>

**TripWeave** is a full-stack travel itinerary web application built with **PHP**, **MySQL**, **Bootstrap 5**, and **Vanilla JavaScript**.
Plan multi-day trips, organize your destinations, and travel with confidence — all in one beautiful interface.

[🐛 Report Bug](../../issues) &nbsp;·&nbsp; [✨ Request Feature](../../issues)

</div>

---

## 📸 Preview

<div align="center">

| 🏠 Home Page | 📅 Planner | 📊 Dashboard |
|:---:|:---:|:---:|
| *Landing page with hero section, destinations & features* | *Day-by-day itinerary builder* | *All trips at a glance* |

</div>

---

## ✨ Features

<table>
<tr>
<td width="50%">

### 🎨 Frontend
- **Responsive Design** — Bootstrap 5 grid (mobile-first)
- **Dynamic Itinerary** — Add/remove stops without page reload
- **Custom Date Picker** — Vanilla JS, no external libraries
- **Form Validation** — Client-side + server-side
- **CSS Animations** — `popIn`, `slideUp`, `float`, `pulse-ring`
- **Scroll Progress Bar** — Gradient bar at the top
- **4 Full Pages** — Home, Planner, Dashboard, Contact

</td>
<td width="50%">

### 🔒 Backend & Security
- **Auth System** — Register / Login / Logout with PHP sessions
- **bcrypt Passwords** — `password_hash(PASSWORD_BCRYPT)`
- **SQL Injection Prevention** — Prepared statements everywhere
- **XSS Prevention** — `htmlspecialchars()` on all output
- **IDOR Protection** — Ownership verified before every write
- **Session Security** — `session_regenerate_id(true)` on login
- **Flash Messages** — One-time session messages across redirects

</td>
</tr>
</table>

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| **Frontend** | HTML5, CSS3, Vanilla JavaScript, Bootstrap 5, Bootstrap Icons |
| **Backend** | PHP 8.x |
| **Database** | MySQL 8.x (MySQLi with prepared statements) |
| **Typography** | Google Fonts — Playfair Display + Inter |
| **Local Server** | XAMPP / WAMP |

---

## 📁 Project Structure

```
tripweave/
│
├── 📁 auth/
│   ├── login.php           # Login handler + form
│   ├── register.php        # Registration handler + form
│   └── logout.php          # Session destroy + redirect
│
├── 📁 css/
│   └── style.css           # Main stylesheet (variables, components, animations)
│
├── 📁 js/
│   ├── main.js             # Global: scroll progress, back-to-top, AOS, navbar
│   ├── datepicker.js       # Custom vanilla-JS date picker (single + range mode)
│   ├── planner.js          # Itinerary planner: stop state, day tabs, summary
│   └── validate.js         # Reusable client-side form validation utilities
│
├── 📁 includes/
│   ├── db.php              # MySQLi database connection
│   └── functions.php       # Helpers: sanitize, redirect, flash, isLoggedIn, e()
│
├── 📁 images/              # Static assets & images
│
├── index.php               # 🏠 Home / Landing page
├── planner.php             # 📅 Itinerary Planner (create trip + add/remove stops)
├── dashboard.php           # 📊 User Dashboard (all trips, stats, delete)
├── contact.php             # 📬 Contact form (saves to DB)
├── database.sql            # 🗄️  Schema + sample data
└── README.md               # 📖 You are here!
```

---

## 🚀 Getting Started

### Prerequisites

- ✅ [XAMPP](https://www.apachefriends.org/) or [WAMP](https://www.wampserver.com/) installed
- ✅ Apache and MySQL services **started**
- ✅ PHP 8.x

### Installation

**1. Clone the repository**
```bash
git clone https://github.com/YOUR_USERNAME/tripweave.git
```

**2. Move to your server root**
```
# XAMPP (Windows)
C:\xampp\htdocs\tripweave\

# WAMP (Windows)
C:\wamp64\www\tripweave\
```

**3. Import the database**

Open `http://localhost/phpmyadmin` → **Import** → Select `database.sql` → **Go**

Or via command line:
```bash
mysql -u root -p < database.sql
```

**4. Configure database connection**

Open `includes/db.php` and update if needed:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');        // Add your MySQL password if needed
define('DB_NAME', 'tripweave');
```

**5. Open in browser** 🎉
```
http://localhost/tripweave/index.php
```

---

## 🔑 Demo Credentials

A sample account is seeded in `database.sql` for quick testing:

| Field | Value |
|---|---|
| **Email** | `alex@example.com` |
| **Password** | `Password1` |

> ⚠️ **Note:** This account is for **development/demo only**. Passwords are stored as bcrypt hashes — never in plain text.

---

## ⚡ JavaScript Features

| Feature | File | Trigger |
|---|---|---|
| Dynamic stop add/remove | `planner.js` | Stop form submit / delete button |
| Live summary panel update | `planner.js` | Any state change |
| Day-tab switching | `planner.js` | Click day tab |
| Custom date picker | `datepicker.js` | Click date input |
| Form validation | `validate.js` | Form submit |
| Smooth scrolling | `main.js` | Anchor `href="#..."` clicks |
| Scroll progress bar | `main.js` | Window scroll |
| Back-to-top button | `main.js` | Scroll past 400px |
| Animate on scroll | `main.js` | IntersectionObserver |
| Destination modal | `index.php` | Click destination card |
| Stop detail modal | `planner.php` | Click eye icon |
| Delete confirm modal | `dashboard.php` | Click delete button |

---

## 🔐 Security Checklist

- [x] `password_hash(PASSWORD_BCRYPT)` — passwords never stored in plain text
- [x] `password_verify()` — used for login comparison
- [x] `session_regenerate_id(true)` — session fixation prevention
- [x] Prepared statements with `bind_param()` — SQL injection prevention
- [x] `htmlspecialchars()` / `e()` on all output — XSS prevention
- [x] `strip_tags()` + `trim()` on all input — input sanitization
- [x] Ownership check before delete/update — IDOR prevention
- [x] Flash messages via session — safe across redirects
- [x] Cascade deletes — deleting a trip removes all its stops (FK `ON DELETE CASCADE`)

---

## 🎨 Design System

| Token | Value | Usage |
|---|---|---|
| **Primary** | `#14919B` (Deep Teal) | Buttons, links, accents |
| **Secondary** | `#F2C07A` (Warm Sand) | Highlights, badges |
| **Accent** | `#E07B54` (Coral Orange) | CTA, warnings |
| **Background** | `#1A2634` (Dark Navy) | Page background |
| **Heading Font** | Playfair Display | All headings |
| **Body Font** | Inter | All body text |

---

## 🤝 Contributing

Contributions, issues, and feature requests are welcome!
Feel free to check the [issues page](../../issues).

1. Fork the project
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

## 📄 License

Distributed under the MIT License. See `LICENSE` for more information.

---

## 🙏 Credits & Acknowledgements

- **[Bootstrap 5](https://getbootstrap.com/)** — CSS framework & grid system
- **[Bootstrap Icons](https://icons.getbootstrap.com/)** — Icon library
- **[Google Fonts](https://fonts.google.com/)** — Playfair Display + Inter

---

<div align="center">

Made with ❤️ by **TripWeave Team**

⭐ **Star this repo if you find it helpful!** ⭐

</div>
