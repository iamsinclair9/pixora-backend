<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Api\ImageController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\RatingController;
use App\Http\Controllers\Api\UserController;

Route::prefix('v1')->group(function () {
    // Auth
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    
    // Public Image Read
    Route::get('/images', [ImageController::class, 'index']);
    Route::get('/images/search', [ImageController::class, 'search']);
    Route::get('/images/{image}', [ImageController::class, 'show']);
    Route::get('/images/{image}/comments', [CommentController::class, 'index']);
    Route::get('/images/{image}/ratings', [RatingController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/user', [AuthController::class, 'user']);

        // Creator Only
        Route::middleware('role:creator')->group(function () {
            Route::post('/uploads/sas', [UploadController::class, 'sas']);
            Route::post('/uploads/suggest', [UploadController::class, 'suggest']);
            Route::post('/uploads/confirm', [UploadController::class, 'confirm']);
            Route::put('/images/{image}', [ImageController::class, 'update']);
            Route::delete('/images/{image}', [ImageController::class, 'destroy']);
        });

        // Any authenticated user
        Route::post('/images/{image}/comments', [CommentController::class, 'store']);
        Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);
        Route::post('/images/{image}/rate', [RatingController::class, 'store']);
        Route::post('/images/{image}/interact', [ImageController::class, 'interact']);
        Route::post('/images/{image}/bookmark', [UserController::class, 'toggleBookmark']);

        // Profile
        Route::get('/me',            [UserController::class, 'me']);
        Route::get('/me/images',     [UserController::class, 'myImages']);
        Route::get('/me/bookmarks',  [UserController::class, 'bookmarks']);

        // URL upload (creator only)
        Route::middleware('role:creator')->post('/uploads/from-url', [UserController::class, 'uploadFromUrl']);
    });
});
