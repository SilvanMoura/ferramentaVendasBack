<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AmazonScrapeController;
use App\Http\Controllers\MercadoLivreController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/login', [ AuthController::class, 'login' ]);
Route::post('/register', [ AuthController::class, 'register' ]);

Route::get('/scrape/{asin}', [AmazonScrapeController::class, 'scrape']);
Route::get('/calculatorMargin/{asin}', [AmazonScrapeController::class, 'calculatorMargin']);

Route::get('/product/{itemId}', [MercadoLivreController::class, 'getProductDetails']);

Route::get('/welcome', [MercadoLivreController::class, 'welcome']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
