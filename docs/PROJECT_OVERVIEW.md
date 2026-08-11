# Project Overview

## Application

Lumina Beauty is a Laravel e-commerce application for cosmetics and beauty products. It began as a Bachelor's thesis / portfolio-scale software engineering project and now serves as an existing codebase for continued development.

## Purpose

The application demonstrates a full-stack beauty commerce experience: browsing a seeded catalog, filtering products, managing favorites and carts, placing orders, and administering catalog and order data.

## Target Users

- Guests: browse products, use favorites/cart in session state, and place orders.
- Customers: register/login, preserve cart/favorite state across sessions, reset passwords, and place orders linked to their account.
- Admin: manage products, categories, brands, orders, customers, reports, discounts, and settings through a protected admin panel.

## Business Domain

The catalog is organized around beauty categories such as skin care, hair care, makeup, perfume, and body care. Products include brand, category, price/sale price, stock, active status, image gallery, and filter metadata such as product type, properties, gender, and size.

## Primary User Flows

- Browse home page, category, brand, sales, and hot-trends listings.
- Search and filter products.
- View product detail pages.
- Add/remove favorites as a guest or authenticated user.
- Add/update/remove cart items as a guest or authenticated user.
- Checkout into a persisted order with order item snapshots.
- Register/login/logout and reset a customer password.
- Admin login, manage catalog data, review orders, change order status, and inspect reports.

## Current Scope

The current app is a Laravel monolith with server-rendered Blade views, static public CSS/JS, DDEV local development, MariaDB/MySQL schema support, and Mailpit/log/array-compatible mail flow for development and tests.

See [CURRENT_STATE.md](CURRENT_STATE.md), [ARCHITECTURE.md](ARCHITECTURE.md), and [TECH_STACK.md](TECH_STACK.md) for implementation details.

## Non-Goals And Current Limitations

- No real payment gateway is implemented.
- No production deployment is documented as complete.
- Gmail SMTP is not configured in repository documentation or code.
- Google OAuth, login 2FA, guest checkout verification, chatbot, FastAPI, and OpenAI API integration are roadmap items only.
- The admin model is intentionally simple: one developer-created admin account represented by `users.is_admin`.
- Order status transition rules are currently permissive and need hardening before production use.
