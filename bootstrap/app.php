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
        $middleware->append(\App\Http\Middleware\AuditRequestMiddleware::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\EnsureRoutePermission::class);
        $middleware->prependToPriorityList(
            \Illuminate\Auth\Middleware\Authenticate::class,
            \App\Http\Middleware\AuditRequestMiddleware::class
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
