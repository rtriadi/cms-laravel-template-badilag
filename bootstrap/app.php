<?php

use App\Http\Middleware\EnsureSetupCompletedMiddleware;
use App\Http\Middleware\EnsureTwoFactorConfirmedMiddleware;
use App\Http\Middleware\HandleRedirectsMiddleware;
use App\Http\Middleware\SecurityHeadersMiddleware;
use App\Http\Middleware\TrackPageVisitMiddleware;
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
        $rawProxies = trim((string) (app()->bound('config') ? config('satker.trusted_proxies', '') : env('TRUSTED_PROXIES', '')));
        $trustedProxies = ($rawProxies === '' || $rawProxies === '*')
            ? null
            : (array_values(array_filter(
                array_map('trim', explode(',', $rawProxies)),
                static fn (string $ip): bool => $ip !== '' && $ip !== '*',
            )) ?: null);

        $middleware->trustProxies(at: $trustedProxies);

        $middleware->trustHosts(
            at: function (): array {
                $raw = trim((string) (config('satker.trusted_hosts') ?? env('TRUSTED_HOSTS', '')));
                if ($raw !== '') {
                    $patterns = array_values(array_filter(array_map('trim', explode(',', $raw))));
                    if ($patterns !== []) {
                        return $patterns;
                    }
                }

                $appHost = parse_url((string) config('app.url', 'http://localhost'), PHP_URL_HOST);
                if (is_string($appHost) && $appHost !== '' && app()->environment('production')) {
                    return [
                        '^(.+\.)?'.preg_quote($appHost, '/').'$',
                    ];
                }

                return [];
            },
            subdomains: false,
        );

        $middleware->append(SecurityHeadersMiddleware::class);
        $middleware->web(append: [
            HandleRedirectsMiddleware::class,
            EnsureSetupCompletedMiddleware::class,
            TrackPageVisitMiddleware::class,
        ]);
        $middleware->alias([
            'two-factor.required' => EnsureTwoFactorConfirmedMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
