# Lumina Beauty

A full-stack e-commerce platform for cosmetic products developed as my Bachelor's thesis in Software Engineering.

## Features

- User Authentication & Authorization
- Product Management
- Category Management
- Brand Management
- Shopping Cart
- Favorites (Wishlist)
- Checkout System
- Order Management
- Admin Dashboard
- Product Filtering
- Email Notifications (Mailpit)

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
- Mailpit

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

- Prevent purchasing products when stock reaches zero.
- Replace product deletion with inactive/archive status to preserve order history.
- Payment gateway integration.
- Product reviews and ratings.

