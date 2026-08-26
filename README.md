# Lumina Beauty

A full-stack e-commerce platform for cosmetic products developed as my Bachelor's thesis in Software Engineering.

## Features

- Customer Authentication & Authorization
- Registration Email OTP Verification
- Customer Password Login Email 2FA
- Dedicated Admin Login Email 2FA
- Guest Checkout Email OTP Verification
- Google OAuth Customer Login
- Product Management
- Category Management
- Brand Management
- Shopping Cart
- Favorites (Wishlist)
- Checkout System
- Order Management
- Admin Dashboard
- Product Filtering
- Product Knowledge Layer for structured catalog suitability, ingredient, routine, and attribute metadata
- Deterministic Product Retrieval, Comparison, Recommendation, And Skincare Routine Foundations
- Gmail SMTP Email Notifications Through Laravel Mail
- Security Headers, Audit Logging, Rate Limiting, And Session Protection

## Technologies

- Laravel
- PHP
- Blade
- JavaScript
- HTML
- CSS
- MariaDB / MySQL
- Docker
- DDEV
- Gmail SMTP for Laravel runtime mail
- Mailpit as an optional DDEV local inspection utility only

## Installation

```bash
git clone https://github.com/vesaaas/lumina-beauty.git

cd lumina-beauty

composer install

npm install

cp .env.example .env

php artisan key:generate

php artisan migrate --seed

npm run dev

ddev start
```

## Screenshots

Coming soon.

## Project Purpose

This project was developed as my Bachelor's thesis in Computer Science (Software Engineering). It demonstrates a complete Laravel-based e-commerce application following MVC architecture and modern web development practices.

## Future Improvements

- AI chatbot using the Product Knowledge Layer as structured catalog context.
- Payment gateway integration.
- Product reviews and ratings.
