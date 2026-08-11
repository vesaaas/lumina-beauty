<?php

use App\Http\Controllers\Auth\AccountAuthController;
use App\Http\Controllers\Auth\EmailVerificationOtpController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StorefrontController::class, 'home'])->name('home');
Route::get('/products', [StorefrontController::class, 'products'])->name('products.index');
Route::get('/products/{product}', [StorefrontController::class, 'product'])->name('products.show');
Route::get('/categories', [StorefrontController::class, 'categories'])->name('categories.index');
Route::get('/categories/{category}', [StorefrontController::class, 'category'])->name('categories.show');
Route::get('/brands', [StorefrontController::class, 'brands'])->name('brands.index');
Route::get('/brands/{brand}', [StorefrontController::class, 'brand'])->name('brands.show');
Route::get('/favorites', [StorefrontController::class, 'favorites'])->name('favorites.index');
Route::post('/favorites/{product}', [StorefrontController::class, 'toggleFavorite'])->name('favorites.toggle');
Route::delete('/favorites/{product}', [StorefrontController::class, 'removeFavorite'])->name('favorites.destroy');
Route::get('/cart', [StorefrontController::class, 'cart'])->name('cart.index');
Route::post('/cart/{product}', [StorefrontController::class, 'addToCart'])->name('cart.add');
Route::patch('/cart/products/{product}', [StorefrontController::class, 'updateGuestCart'])->name('cart.guest.update');
Route::delete('/cart/products/{product}', [StorefrontController::class, 'removeGuestCart'])->name('cart.guest.destroy');
Route::patch('/cart/items/{cartItem}', [StorefrontController::class, 'updateCart'])->name('cart.update');
Route::delete('/cart/items/{cartItem}', [StorefrontController::class, 'removeCart'])->name('cart.destroy');
Route::get('/checkout', [StorefrontController::class, 'checkout'])->name('checkout.index');
Route::post('/checkout', [StorefrontController::class, 'placeOrder'])->name('checkout.store');
Route::get('/orders/{order}/thank-you', [StorefrontController::class, 'thankYou'])->name('orders.thank-you');
Route::get('/account', fn () => redirect()->route('home')->with('account_modal', true))->name('account.index');
Route::get('/login', fn () => redirect()->route('home')->with('account_modal', true))->name('login');
Route::get('/register', fn () => redirect()->route('home')->with('account_modal', true))->name('register');
Route::post('/login', [AccountAuthController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('login.submit');
Route::post('/register', [AccountAuthController::class, 'register'])
    ->middleware('throttle:3,10')
    ->name('register.submit');

Route::get('/forgot-password', [AccountAuthController::class, 'showForgotPassword'])
    ->middleware('guest')
    ->name('password.request');
Route::post('/forgot-password', [AccountAuthController::class, 'sendPasswordResetLink'])
    ->middleware(['guest', 'throttle:3,10'])
    ->name('password.email');

Route::get('/reset-password/{token}', [AccountAuthController::class, 'showResetPassword'])
    ->middleware('guest')
   ->name('password.reset');
Route::post('/reset-password', [AccountAuthController::class, 'resetPassword'])
    ->middleware(['guest', 'throttle:3,10'])
    ->name('password.update');

Route::get('/admin/login', [AccountAuthController::class, 'showAdminLogin'])
    ->name('admin.login');

Route::post('/admin/login', [AccountAuthController::class, 'adminLogin'])
    ->middleware('throttle:5,1')
    ->name('admin.login.submit');

Route::post('/logout', [AccountAuthController::class, 'logout'])
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [EmailVerificationOtpController::class, 'show'])
        ->name('verification.otp.show');

    Route::post('/email/verify', [EmailVerificationOtpController::class, 'verify'])
        ->middleware('throttle:5,1')
        ->name('verification.otp.verify');

    Route::post('/email/verify/resend', [EmailVerificationOtpController::class, 'resend'])
        ->middleware('throttle:3,1')
        ->name('verification.otp.resend');
});
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/products', [AdminController::class, 'products'])->name('products.index');
    Route::get('/products/create', [AdminController::class, 'createProduct'])->name('products.create');
    Route::post('/products', [AdminController::class, 'storeProduct'])->name('products.store');
    Route::get('/products/{product}/edit', [AdminController::class, 'editProduct'])->name('products.edit');
    Route::put('/products/{product}', [AdminController::class, 'updateProduct'])->name('products.update');
    Route::get('/categories', [AdminController::class, 'categories'])->name('categories.index');
    Route::post('/categories', [AdminController::class, 'storeCategory'])->name('categories.store');
    Route::put('/categories/{category}', [AdminController::class, 'updateCategory'])->name('categories.update');
    Route::delete('/categories/{category}', [AdminController::class, 'deleteCategory'])
        ->name('categories.destroy');
    Route::get('/brands', [AdminController::class, 'brands'])->name('brands.index');
    Route::post('/brands', [AdminController::class, 'storeBrand'])->name('brands.store');
    Route::put('/brands/{brand}', [AdminController::class, 'updateBrand'])->name('brands.update');
    Route::delete('/brands/{brand}', [AdminController::class, 'deleteBrand'])
        ->name('brands.destroy');
    Route::get('/orders', [AdminController::class, 'orders'])->name('orders.index');
    Route::get('/orders/{order}', [AdminController::class, 'order'])->name('orders.show');

    Route::patch('/orders/{order}', [AdminController::class, 'updateOrder'])
        ->name('orders.update');
    Route::get('/users', [AdminController::class, 'users'])->name('users.index');
    Route::get('/reports', [AdminController::class, 'reports'])->name('reports');
    Route::get('/discounts', [AdminController::class, 'discounts'])->name('discounts');
    Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
});
Route::get('/about-us', [StorefrontController::class, 'about'])->name('about');
Route::post('/about-us', [StorefrontController::class, 'sendAboutMessage'])
    ->middleware('throttle:3,10')
    ->name('about.send');
Route::get('/contact-us', [StorefrontController::class, 'contact'])->name('contact');
Route::post('/contact-us', [StorefrontController::class, 'sendContactMessage'])
    ->middleware('throttle:3,10')
    ->name('contact.send');

Route::get('/hot-trends', [StorefrontController::class, 'hotTrends'])
    ->name('hot-trends');

Route::get('/sales', [StorefrontController::class, 'sales'])
    ->name('sales');
