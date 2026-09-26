# TripWeave — Travel Itinerary Planner

> *Weave your perfect journey, day by day.*

**TripWeave** is a full-stack travel itinerary web application built for the ICT2206 Web Technologies module. It lets users plan multi-day trips by organizing destinations and activities into a clean, day-by-day schedule — all backed by a PHP/MySQL backend with secure authentication.

---

## Features Implemented

### Frontend
| Feature | Details |
|---|---|
| **Responsive Layout** | Bootstrap 5 grid — works on mobile, tablet, and desktop |
| **Dynamic Itinerary Updates** | Add/remove stops without page reload (JS state); live trip summary panel |
| **Custom Date Picker** | Vanilla JS date picker (`datepicker.js`) — no external libraries |
| **Form Validation** | Client-side (`validate.js`) + server-side for all forms |
| **Smooth Scrolling** | In-page anchor links on the Home page |
| **Event Handling** | Hover effects, tooltips, Bootstrap modals (destination detail, stop detail, delete confirmation) |
| **CSS Animations** | `popIn` on new stops, `slideUp` on scroll, `float` on hero card, `pulse-ring` on route dots |
| **Scroll Progress Bar** | Thin gradient bar at the very top of the page |
| **4 Pages** | Home, Planner, Dashboard, Contact |

### Backend
| Feature | Details |
|---|---|
| **Auth** | Register / Login / Logout with PHP sessions |
| **Password Security** | `password_hash(PASSWORD_BCRYPT)` + `password_verify()` |
| **Session Security** | `session_regenerate_id(true)` on login |
| **Prepared Statements** | All DB queries use `bind_param()` — no raw SQL concatenation |
| **XSS Prevention** | `htmlspecialchars()` / `e()` on every output; `sanitize()` on every input |
| **IDOR Protection** | Trip/stop ownership verified via `user_id` before any write |
| **Flash Messages** | One-time session messages across redirects |
| **Contact Form** | Saves to DB; PHPMailer stub included (commented out) |
| **Cascade Deletes** | Deleting a trip also removes all its stops (FK `ON DELETE CASCADE`) |

### Visual Identity
- **Colors:** Deep Teal (`#14919B`) + Warm Sand (`#F2C07A`) + Coral Orange (`#E07B54`) on a dark navy (`#1A2634`) base
- **Fonts:** Playfair Display (headings) + Inter (body) via Google Fonts
- **Logo:** "T" mark in a rounded square, gradient wordmark "TripWeave" — consistent across all pages

---

## File Structure

```
project/
├── css/
│   └── style.css           Main stylesheet (variables, components, animations)
├── js/
│   ├── main.js             Global: scroll progress, back-to-top, AOS, navbar
│   ├── datepicker.js       Custom vanilla-JS date picker (single + range mode)
│   ├── planner.js          Itinerary planner: stop state, day tabs, summary
│   └── validate.js         Reusable client-side form validation utilities
├── images/                 (Place any custom images here)
├── includes/
│   ├── db.php              MySQLi database connection
│   └── functions.php       Helpers: sanitize, redirect, flash, isLoggedIn, e(), fmtDate()
├── auth/
│   ├── register.php        Registration handler + form
│   ├── login.php           Login handler + form
│   └── logout.php          Session destroy + redirect
├── index.php               Home / Landing page
├── planner.php             Itinerary Planner (create trip + add/remove stops)
├── dashboard.php           User dashboard (all trips, stats, delete)
├── contact.php             Contact form (saves to messages table)
├── database.sql            Schema + sample data (import via phpMyAdmin)
└── README.md               This file
```

---

## How to Run Locally (XAMPP / WAMP)

### Prerequisites
- [XAMPP](https://www.apachefriends.org/) or [WAMP](https://www.wampserver.com/) installed
- Apache and MySQL services started

### Step 1 — Copy Files
1. Copy the entire `project/` folder into your web server's document root:
   - **XAMPP:** `C:\xampp\htdocs\tripweave\`
   - **WAMP:** `C:\wamp64\www\tripweave\`

### Step 2 — Import the Database
1. Open your browser and go to `http://localhost/phpmyadmin`
2. Click **"Import"** in the top navigation bar
3. Click **"Choose File"** and select `project/database.sql`
4. Scroll down and click **"Go"**
5. You should see a success message — the `tripweave` database is now ready

   > **Alternative (command line):**
   > ```bash
   > mysql -u root -p < database.sql
   > ```

### Step 3 — Configure Database (if needed)
Open `project/includes/db.php` and check/update:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');        // Add your password if MySQL requires one
define('DB_NAME', 'tripweave');
```

### Step 4 — Open in Browser
Navigate to:
```
http://localhost/tripweave/index.php
```

---

## Sample Login Credentials
A sample account is included in `database.sql`:

| Field    | Value              |
|----------|--------------------|
| Email    | `alex@example.com` |
| Password | `Password1`        |

> ⚠️ This is for development/demo only. The password is stored as a bcrypt hash in the database — it is never stored in plain text.

---

## JavaScript Features (Summary)

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
| Destination modal | `index.php` inline | Click destination card |
| Stop detail modal | `planner.php` inline | Click eye icon |
| Delete confirm modal | `dashboard.php` inline | Click delete button |
| CSS hover transitions | `style.css` | CSS `:hover` pseudo-class |

---

## PHP Security Checklist

- [x] `password_hash(PASSWORD_BCRYPT)` — passwords never stored in plain text
- [x] `password_verify()` — used for login comparison
- [x] `session_regenerate_id(true)` — session fixation prevention
- [x] Prepared statements (`bind_param`) — SQL injection prevention
- [x] `htmlspecialchars()` / `e()` on all output — XSS prevention
- [x] `strip_tags()` + `trim()` on all input — input sanitization
- [x] Ownership check before delete/update — IDOR prevention
- [x] Flash messages via session — safe across redirects

---

## Credits

- **Bootstrap 5** — CSS framework / grid system
- **Bootstrap Icons** — Icon library
- **Google Fonts** — Playfair Display + Inter typography
- Built for **ICT2206 Web Technologies** module
