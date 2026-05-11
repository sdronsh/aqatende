<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'platform' => \App\Http\Middleware\EnsurePlatformAdmin::class,
            'terms-accepted' => \App\Http\Middleware\EnsureClinicTermsAccepted::class,
            'whatsapp-module' => \App\Http\Middleware\EnsureWhatsappModuleEnabled::class,
        ]);

        $middleware->appendToGroup('web', \App\Http\Middleware\EnsureActiveCompany::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
