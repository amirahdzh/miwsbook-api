<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BookController;
use App\Http\Controllers\API\AuthorController;
use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\API\BorrowController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\CategoryController;

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

Route::prefix('v1')->group(function () {
    Route::apiResource('book', BookController::class);
    Route::get('/books/search', [BookController::class, 'search']);
    Route::apiResource('category', CategoryController::class);
    Route::get('/category/{categoryId}/books', [CategoryController::class, 'getBooksByCategory']);
    Route::post('/books/{bookId}/categories', [BookController::class, 'attachCategories']);
    Route::delete('/books/{bookId}/categories', [BookController::class, 'detachCategories']);
    Route::apiResource('author', AuthorController::class);
    Route::apiResource('role', RoleController::class);
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api');
    });
    Route::get('/me', [AuthController::class, 'getUser'])->middleware('auth:api');
    Route::post('/profile', [ProfileController::class, 'store'])->middleware('auth:api');
    Route::post('/borrow', [BorrowController::class, 'store'])->middleware('auth:api');
    Route::get('/borrow', [BorrowController::class, 'index'])->middleware('auth:api', 'isOwner');
    Route::put('/borrow/{id}', [BorrowController::class, 'update'])->middleware('auth:api', 'isOwner');
    Route::delete('/borrow/{id}', [BorrowController::class, 'destroy'])->middleware('auth:api', 'isOwner');
    Route::get('/borrow/my-borrow', [BorrowController::class, 'borrowedByUser'])->middleware('auth:api');

    Route::get('/users', [UserController::class, 'getAllUsers'])->middleware('auth:api', 'isOwner');
    Route::get('/user/role/{roleId}', [UserController::class, 'getUsersByRole'])->middleware('auth:api', 'isOwner');
    Route::put('/user/{id}', [UserController::class, 'updateUser'])->middleware('auth:api', 'isOwner');
    Route::delete('/user/{id}', [UserController::class, 'deleteUser'])->middleware('auth:api', 'isOwner');
});
