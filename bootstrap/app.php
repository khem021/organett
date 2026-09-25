<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\CheckActiveUser;
use App\Http\Middleware\CheckFarmFeature;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Render only appends to X-Forwarded-For ("client, cloudflare, 10.x"), so the leftmost entry is
        // whatever the visitor sent. Trust just Render's internal hops and Cloudflare so ip() is the
        // real client. Cloudflare ranges: https://www.cloudflare.com/ips/
        $middleware->trustProxies(at: [
            'REMOTE_ADDR', 'PRIVATE_SUBNETS',
            '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22',
            '141.101.64.0/18', '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20',
            '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
            '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
            '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32',
            '2405:8100::/32', '2a06:98c0::/29', '2c0f:f248::/32',
        ]);

        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'feature' => CheckFarmFeature::class,
        ]);
        // Kick deactivated users out on every web request
        $middleware->appendToGroup('web', CheckActiveUser::class);
        $middleware->appendToGroup('web', SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
