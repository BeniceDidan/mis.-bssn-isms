<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Railway (like most PaaS hosts) terminates TLS at its own edge
        // proxy and forwards plain HTTP to the container — without this,
        // Laravel never sees the request as secure, so it generates
        // http:// URLs on an https:// page (browsers block those as mixed
        // content). Trusting all proxies is standard/safe here since
        // Railway's edge is the actual trust boundary, not this app.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
