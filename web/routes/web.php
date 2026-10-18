<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CompareController;
use App\Http\Controllers\FinderController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

// Public catalog. No price, stock or cart anywhere here.
Route::get('/', [CatalogController::class, 'home'])->name('home');
Route::get('/c/{category}', [CatalogController::class, 'category'])->name('category');
Route::get('/p/{product}', [ProductController::class, 'show'])->name('product');
Route::get('/search', [SearchController::class, 'index'])->name('search');
Route::get('/search/suggest', [SearchController::class, 'suggest'])->middleware('throttle:120,1')->name('search.suggest');
Route::get('/compare', [CompareController::class, 'show'])->name('compare');
Route::get('/finder', [FinderController::class, 'index'])->name('finder');
Route::get('/finder/results', [FinderController::class, 'results'])->name('finder.results');
Route::get('/about', [CatalogController::class, 'about'])->name('about');
Route::post('/inquiry', [InquiryController::class, 'log'])->middleware('throttle:20,1')->name('inquiry.log');

// Admin panel. No public sign-up; the first «مدیر کل» is created once at /admin/setup.
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('setup', [Admin\SetupController::class, 'show'])->name('setup');
    Route::post('setup', [Admin\SetupController::class, 'store'])->middleware('throttle:10,1');

    Route::middleware('guest')->group(function () {
        Route::get('login', [Admin\AuthController::class, 'show'])->name('login');
        Route::post('login', [Admin\AuthController::class, 'login'])->middleware('throttle:30,1');
    });

    Route::middleware(['auth', 'active'])->group(function () {
        Route::post('logout', [Admin\AuthController::class, 'logout'])->name('logout');
        Route::get('/', Admin\OverviewController::class)->name('overview');

        Route::prefix('products')->name('products.')->controller(Admin\ProductController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('create', 'create')->name('create');
            Route::get('lookup', 'lookup')->name('lookup');
            Route::post('/', 'store')->name('store');
            Route::post('bulk', 'bulk')->name('bulk');
            Route::get('{product}/edit', 'edit')->name('edit');
            Route::put('{product}', 'update')->name('update');
            Route::post('{product}/duplicate', 'duplicate')->name('duplicate');
            Route::delete('{product}', 'destroy')->name('destroy');
        });

        Route::middleware('role:admin')->group(function () {
            Route::get('settings', [Admin\SettingsController::class, 'show'])->name('settings');
            Route::put('settings', [Admin\SettingsController::class, 'update']);
        });

        Route::get('{section}', Admin\SoonController::class)->name('soon')
            ->whereIn('section', array_keys(Admin\SoonController::SECTIONS));
    });
});
