<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ModuleController;
use App\Http\Controllers\Api\V1\SectionController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/login', [
            AuthController::class,
            'login',
        ])->middleware('throttle:login');

        Route::middleware([
            'auth:sanctum',
            'company.access',
        ])->group(function () {
            Route::post('/logout', [
                AuthController::class,
                'logout',
            ]);

            Route::get('/me', [
                AuthController::class,
                'me',
            ]);
        });
    });

    Route::middleware([
        'auth:sanctum',
        'company.access',
    ])->group(function () {
        Route::get('/users', [
            UserController::class,
            'index',
        ])->middleware('permission:usuarios.view');

        Route::post('/users', [
            UserController::class,
            'store',
        ])->middleware('permission:usuarios.create');

        Route::match(['put', 'patch'], '/users/{user}', [
            UserController::class,
            'update',
        ])->middleware('permission:usuarios.update');

        Route::delete('/users/{user}', [
            UserController::class,
            'destroy',
        ])->middleware('permission:usuarios.delete');

        Route::get('/modules', [
            ModuleController::class,
            'index',
        ]);

        Route::get('/company/modules', [
            ModuleController::class,
            'companyModules',
        ]);

        Route::post('/company/modules/{module}/enable', [
            ModuleController::class,
            'enable',
        ]);

        Route::post('/company/modules/{module}/disable', [
            ModuleController::class,
            'disable',
        ]);

        Route::get('/sections', [
            SectionController::class,
            'index',
        ]);
    });
});