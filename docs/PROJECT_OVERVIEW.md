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

The catalog is organized around beauty categories such as skin care, hair care, makeup, perfume, and body care. Products include brand, category, price/sale price, stock, active status, image gallery, storefront filter metadata such as product type, properties, gender, and size, and Phase 2 Product Knowledge metadata for explicit suitability, concerns, benefits, ingredients, usage, routine steps, and nullable factual attributes.

## Primary User Flows

- Browse home page, category, brand, sales, and hot-trends listings.
- Search and filter products.
- View product detail pages.
- Add/remove favorites as a guest or authenticated user.
- Add/update/remove cart items as a guest or authenticated user.
- Checkout into a persisted order with order item snapshots; guests verify checkout email by OTP before order creation.
- Register, verify email by OTP, login with password plus email 2FA, login with Google OAuth, logout, and reset a customer password.
- Admin login with dedicated email 2FA, manage catalog data, review orders, change order status, and inspect reports.

## Current Scope

The current app is a Laravel monolith with server-rendered Blade views, static public CSS/JS, DDEV local development, MariaDB/MySQL schema support, Gmail SMTP runtime mail configuration, database queues for auth/security mail, array/sync mail behavior for tests, and a Laravel/MariaDB Product Knowledge Layer for deterministic catalog retrieval, comparison, recommendations, and skincare routine foundations.

See [CURRENT_STATE.md](CURRENT_STATE.md), [ARCHITECTURE.md](ARCHITECTURE.md), and [TECH_STACK.md](TECH_STACK.md) for implementation details.

## Non-Goals And Current Limitations

- No real payment gateway is implemented.
- No production deployment is documented as complete.
- Real Gmail App Passwords and Google OAuth secrets must remain only in `.env` or deployment secret storage.
- React chatbot UI, Python/FastAPI AI service, OpenAI Responses API integration, embeddings/vector database, and advanced image analysis are roadmap items only and are not implemented.
- The admin model is intentionally simple: one developer-created admin account represented by `users.is_admin`.
