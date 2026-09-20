<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\CustomerAuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::get('/cart/panel', [CartController::class, 'panel'])->name('cart.panel');
Route::post('/cart/items', [CartController::class, 'add'])->name('cart.add');
Route::patch('/cart/items/{variant}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/items/{variant}', [CartController::class, 'remove'])->name('cart.remove');

Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
Route::post('/wishlist/{product}', [WishlistController::class, 'toggle'])
    ->middleware('throttle:60,1')
    ->name('wishlist.toggle');

Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/checkout', [CheckoutController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('checkout.store');

Route::get('/orders/{order}/thank-you', [OrderController::class, 'thanks'])->name('orders.thanks');
Route::get('/track', [OrderController::class, 'trackForm'])->name('orders.track');
Route::post('/track', [OrderController::class, 'track'])->middleware('throttle:20,1');

Route::post('/products/{product}/reviews', [ReviewController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('reviews.store');

// Customer accounts. Everything here is optional: guest checkout and /track stay open.
Route::middleware('guest')->group(function () {
    Route::get('/account/register', [CustomerAuthController::class, 'showRegister'])->name('account.register');
    Route::post('/account/register', [CustomerAuthController::class, 'register'])->middleware('throttle:10,1');
    Route::get('/account/login', [CustomerAuthController::class, 'showLogin'])->name('account.login');
    Route::post('/account/login', [CustomerAuthController::class, 'login'])->middleware('throttle:10,1');

    Route::get('/account/forgot-password', [PasswordResetController::class, 'showForgot'])->name('password.request');
    Route::post('/account/forgot-password', [PasswordResetController::class, 'sendLink'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/account/reset-password/{token}', [PasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('/account/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:5,1')->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/account', [AccountController::class, 'index'])->name('account.index');
    Route::get('/account/orders/{order}', [AccountController::class, 'order'])->name('account.order');
    Route::get('/account/reviews', [AccountController::class, 'reviews'])->name('account.reviews');
    Route::get('/account/profile', [AccountController::class, 'profile'])->name('account.profile');
    Route::patch('/account/profile', [AccountController::class, 'updateProfile'])->name('account.profile.update');
    Route::patch('/account/password', [AccountController::class, 'updatePassword'])->name('account.password.update');
    Route::post('/account/logout', [CustomerAuthController::class, 'logout'])->name('account.logout');
});

Route::get('/search', SearchController::class)->name('search');
Route::get('/contact', [PageController::class, 'contact'])->name('pages.contact');
Route::post('/contact', [PageController::class, 'sendMessage'])->middleware('throttle:5,1')->name('pages.contact.send');

Route::get('/size-guide', [PageController::class, 'sizeGuide'])->name('pages.size-guide');
Route::get('/{page}', [PageController::class, 'show'])
    ->where('page', 'delivery|returns|about|privacy|terms')
    ->name('pages.show');

// Department-scoped category listing, e.g. /baby/bodysuits. Kept last so static routes win.
Route::get('/{department}/{categorySlug}', [ShopController::class, 'category'])->name('shop.category');
