<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ShopController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/items', [CartController::class, 'add'])->name('cart.add');
Route::patch('/cart/items/{variant}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/items/{variant}', [CartController::class, 'remove'])->name('cart.remove');

Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/checkout', [CheckoutController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('checkout.store');

Route::get('/orders/{order}/thank-you', [OrderController::class, 'thanks'])->name('orders.thanks');
Route::get('/track', [OrderController::class, 'trackForm'])->name('orders.track');
Route::post('/track', [OrderController::class, 'track'])->middleware('throttle:20,1');

Route::get('/search', SearchController::class)->name('search');
Route::get('/contact', [PageController::class, 'contact'])->name('pages.contact');
Route::post('/contact', [PageController::class, 'sendMessage'])->middleware('throttle:5,1')->name('pages.contact.send');

Route::get('/size-guide', [PageController::class, 'sizeGuide'])->name('pages.size-guide');
Route::get('/{page}', [PageController::class, 'show'])
    ->where('page', 'delivery|returns|about|privacy|terms')
    ->name('pages.show');

// Department-scoped category listing, e.g. /baby/bodysuits. Kept last so static routes win.
Route::get('/{department}/{categorySlug}', [ShopController::class, 'category'])->name('shop.category');
