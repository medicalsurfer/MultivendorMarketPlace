# Venmark — Multi-Vendor Marketplace

A modern, feature-rich multi-vendor marketplace built with PHP 8 and MySQL, running on XAMPP. Designed for the Cameroonian market with FCFA currency and mobile money payments.

![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=flat&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=flat&logo=mysql&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green?style=flat)

---

## Screenshots

> Homepage with glassmorphism UI, smart filter bar, and product grid.

---

## Features

### Storefront
- **Product catalogue** with grid layout, vendor badges, delivery chips, and rating stars
- **Smart filter drawer** — slide-in panel (Airbnb/Jumia style) with price range slider, star rating, brand search, and delivery type filters
- **Active filter chips** on the smart bar — click any chip to remove that filter instantly
- **Hero section** with animated search bar and live stats (product count, vendor count)
- **Trust bar** — Free Delivery, Secure Payment, Easy Returns, Quality Guarantee
- **Category strip** for quick browsing
- **Product detail page** with image thumbnails, reviews, and add-to-cart

### UI / UX
- **Glassmorphism** throughout — navbar, cards, drawers, modals, toasts all use `backdrop-filter: blur()`
- **Dark / Light theme** toggle (persisted in `localStorage`, respects `prefers-color-scheme`)
- **Animated login page** — split layout with gradient mesh, floating orbs, glass card, floating label inputs, and demo login chips
- **Skeleton loaders** for product cards while content loads
- **Cart badge bounce** animation on add-to-cart
- **Cart preview panel** — slides in from the right after adding an item
- **Mobile-first** responsive design with hamburger nav drawer

### Shopping
- **Cart** with quantity controls, live price updates, and delivery method selection
- **Favourites** / wishlist
- **Orders history** with status badges (Pending → Processing → Shipped → Delivered)
- **Payment status badges** (Paid / Pending / Failed / Unpaid) per order

### Payments — Campay (MTN MoMo & Orange Money)
- Native Cameroon mobile money via [Campay](https://campay.net/) (free to register)
- USSD push flow — user approves on their phone, no card needed
- Auto-polling every 3 seconds for payment confirmation
- Demo/sandbox mode works out of the box with no API key
- Supports both **MTN Mobile Money** and **Orange Money**

### Multi-Vendor
- Vendor dashboard for managing products
- Per-vendor store pages and product listings
- Admin approval flow for vendors

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8, PDO, MySQL 8 |
| Frontend | Vanilla JS, CSS custom properties |
| Server | Apache (XAMPP) |
| Payments | Campay REST API |
| Auth | PHP sessions with `password_hash` |
| Assets | Inter font, inline SVG icons |

---

## Getting Started

### Requirements
- [XAMPP](https://www.apachefriends.org/) (PHP 8.0+, MySQL 8, Apache)
- A web browser

### Installation

```bash
# 1. Clone into your XAMPP htdocs folder
git clone https://github.com/medicalsurfer/MultivendorMarketPlace.git C:/xampp/htdocs/MVP

# 2. Copy the payment config template
cp config/payment.example.php config/payment.php
```

### Database Setup

1. Start Apache and MySQL in XAMPP Control Panel
2. Visit `http://localhost/MVP/setup.php`
3. This creates all tables and seeds demo data automatically

### Demo Accounts

| Role | Email | Password |
|---|---|---|
| Admin | admin@mlc.com | admin123 |
| Vendor | demovendor@gmail.com | vendor123 |
| Customer | democustomer@gmail.com | customer123 |

---

## Payment Setup (Campay)

1. Register free at **[campay.net](https://campay.net)**
2. Open `config/payment.php` and fill in your credentials:

```php
define('CAMPAY_USERNAME', 'your_username');
define('CAMPAY_PASSWORD', 'your_password');
define('CAMPAY_ENV',      'sandbox'); // change to 'live' for production
```

3. Set your webhook URL in the Campay dashboard:
   `https://yourdomain.com/MVP/api/payment.php`

> **Sandbox mode** works with no API key — payments auto-succeed after 3 seconds for testing.

---

## Project Structure

```
MVP/
├── api/
│   ├── cart.php          # Cart CRUD API
│   ├── favorites.php     # Favourites API
│   ├── payment.php       # Campay payment API (initiate + status)
│   └── products.php      # Products API
├── assets/
│   ├── css/style.css     # Main stylesheet (glassmorphism, dark theme, all components)
│   ├── js/main.js        # Core JS (cart, filters, drawer, theme, cart preview)
│   └── img/
├── config/
│   ├── database.php      # DB connection + FCFA helpers
│   └── payment.example.php  # Payment config template (copy to payment.php)
├── includes/
│   ├── header.php        # Navbar, mobile drawer, theme toggle
│   └── footer.php        # Footer, cart preview panel, newsletter
├── vendor-dashboard/
│   └── index.php         # Vendor product management
├── index.php             # Homepage — product grid + smart filter bar
├── login.php             # Animated login page
├── register.php          # Registration
├── cart.php              # Shopping cart
├── checkout.php          # Payment page (Campay integration)
├── orders.php            # Order history
├── product-details.php   # Product detail page
└── setup.php             # DB setup + seeder (run once)
```

---

## Key Design Decisions

- **Filter drawer over filter bar** — old horizontal filter bar clipped dropdowns due to CSS `overflow` spec. Replaced with a slide-in drawer (Airbnb/Jumia UX pattern) that gives unlimited space for all filters.
- **FCFA currency** — all prices stored in USD internally, converted at 1 USD = 655 XAF for display. Campay API receives the XAF amount directly.
- **No framework dependencies** — pure PHP, vanilla JS, and CSS custom properties. Zero npm, zero composer (except optional).

---

## License

MIT — free to use and modify.
