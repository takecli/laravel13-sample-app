<?php

use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\KeycloakController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'api/v{version}'], function () {
    // 認証
    Route::group(['prefix' => 'auth'], function () {
        // 認証 - Keycloak
        Route::group(['prefix' => 'keycloak'], function () {
            // リダイレクト /api/v1/auth/keycloak/redirect
            Route::get('redirect', [KeycloakController::class, 'redirect'])->name('keycloak.redirect');
            // コールバック /api/v1/auth/keycloak/redirect
            Route::get('callback', [KeycloakController::class, 'callback'])->name('keycloak.callback');
        });
        // 認証 - 汎用
        Route::group([], function () {
            // 認証情報 - 取得 /api/v1/auth
            Route::get('', [AuthController::class, 'getUser']);
        });
    });

    // チーム
    Route::group(['prefix' => 'teams'], function () {
        // チーム一覧 /api/v1/teams
        Route::get('', [TeamController::class, 'listTeam']);
    });
});
