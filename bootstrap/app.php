<?php

use App\Http\Reponses\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        using: function () {
            // ① web_api を先に登録（catch-all より優先させる）
            Route::middleware('web')
                ->group(base_path('routes/web_api.php'));

            // ② 通常の web ルート（/{any} catch-all を含む）を後に
            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // api/* は未認証でもログイン画面へリダイレクトさせない
        // （リダイレクト先を null にして route('login') 解決を回避し、JSON 401 を返す）
        $middleware->redirectGuestsTo(
            fn (Request $request) => $request->is('api/*') ? null : '/',
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // api/* の未認証は login へのリダイレクトではなく 401 JSON を返す
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::unauthenticate();
            }
        });
    })->create();
