<?php

use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CompareController;
use App\Http\Controllers\DeployController;
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

// Post-deploy hook (FTP hosting without SSH); 404 unless DEPLOY_TOKEN is configured.
Route::post('/_deploy', DeployController::class)->middleware('throttle:6,1')->name('deploy');
