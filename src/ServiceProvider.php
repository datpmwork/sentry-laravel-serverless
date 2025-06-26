<?php

namespace DatPM\Sentry\Serverless;

use Sentry\Laravel\Tracing\Middleware;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use DatPM\Sentry\Serverless\Middlewares\ServerlessSentryMiddleware;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        # Overwrite default Middleware class of Sentry
        # Default sentry will send APM in terminate step, after the FPM response was sent
        # Our customization will send the APM when RequestHandled is triggered
        # When this event is dispatched, we will call `parentTerminate` method of SentryTracingMiddleware to send
        # The default `terminate` will be rewritten as empty
        $this->app->bind(Middleware::class, ServerlessSentryMiddleware::class, true);
    }
}
