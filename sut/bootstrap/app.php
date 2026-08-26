<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => App\Http\Middleware\RequireAdminSession::class,
        ]);

        /*
         * The admin gate runs before route model binding, so an unauthenticated probe cannot
         * tell an existing record from a missing one: everything answers 401.
         */
        $middleware->prependToPriorityList(
            before: Illuminate\Routing\Middleware\SubstituteBindings::class,
            prepend: App\Http\Middleware\RequireAdminSession::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /*
         * Failures answer with a short code. The client never receives an exception message,
         * a stack trace, a file path or SQL, whatever APP_DEBUG says.
         */
        //
    })->create();
