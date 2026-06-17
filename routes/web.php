<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Site\ContactController;
use App\Http\Controllers\Site\SiteController;
use Illuminate\Support\Facades\Route;

// 公開前台
Route::middleware(\App\Http\Middleware\SetSiteRootView::class)->group(function () {
    Route::get('/', [SiteController::class, 'home'])->name('home');
    Route::get('/p/{slug}', [SiteController::class, 'page'])->name('site.page');
});

Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

// Auth
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Admin panel
Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::resource('posts', Admin\PostController::class);
    Route::resource('users', Admin\UserController::class)->except(['show']);
    Route::resource('stores', Admin\StoreController::class)->except(['show']);
    Route::resource('products', Admin\ProductController::class)->except(['show']);

    Route::resource('customers', Admin\CustomerController::class)->except(['show']);
    Route::post('customers/{customer}/usages', [Admin\CustomerController::class, 'recordUsage'])->name('customers.usages.store');

    Route::get('inventory', [Admin\InventoryController::class, 'index'])->name('inventory.index');
    Route::post('inventory/adjust', [Admin\InventoryController::class, 'adjust'])->name('inventory.adjust');
    Route::post('inventory/request', [Admin\InventoryController::class, 'requestRestock'])->name('inventory.request');
    Route::post('inventory/logs/{log}/approve', [Admin\InventoryController::class, 'approve'])->name('inventory.approve');
    Route::post('inventory/logs/{log}/reject', [Admin\InventoryController::class, 'reject'])->name('inventory.reject');
    Route::get('inventory-logs', [Admin\InventoryController::class, 'logs'])->name('inventory.logs');

    Route::get('contacts', [Admin\ContactController::class, 'index'])->name('contacts.index');
    Route::patch('contacts/{contact}', [Admin\ContactController::class, 'update'])->name('contacts.update');
    Route::delete('contacts/{contact}', [Admin\ContactController::class, 'destroy'])->name('contacts.destroy');
});
