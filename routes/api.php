<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// Route pour obtenir les informations de l'utilisateur authentifié
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// Groupe de routes protégées par authentification Passport
Route::middleware('auth:api')->prefix('v1')->group(function () {
    Route::get('/articles/{id}', [ArticleController::class, 'get']);
    Route::post('/articles', [ArticleController::class, 'store']);
    Route::get('/users', [ClientController::class, 'users']);
    Route::delete('/articles/{id}', [ArticleController::class, 'delete']);
    Route::post('/articles/update-stock', [ArticleController::class, 'updateStock']);
    Route::patch('/articles/{id}/quantity', [ArticleController::class, 'updateQuantity']);
    Route::get('/articles', [ArticleController::class, 'getAvailable']);
    Route::post('/users-by-etat', [ClientController::class, 'getUsersByEtat']);
    Route::post('/clients/filter-by-telephone', [ClientController::class, 'filterByTelephone']);


    // Autres routes...
});
Route::apiResource('/clients', ClientController::class)->only(['index', 'store','show']);

// Route de connexion accessible sans authentification
Route::post('/loginuser', [AuthController::class, 'login']);

// Exemple de route protégée (authentification requise)
Route::middleware('auth:api')->get('/usernew', function (Request $request) {
    return response()->json('success');
});
