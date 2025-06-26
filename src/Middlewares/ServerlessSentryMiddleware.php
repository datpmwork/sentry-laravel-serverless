<?php

namespace DatPM\Sentry\Serverless\Middlewares;

use Closure;
use Sentry\State\Scope;
use Illuminate\Http\Request;
use Sentry\Laravel\Integration;
use Sentry\Laravel\Tracing\Middleware;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Container\BindingResolutionException;

class ServerlessSentryMiddleware extends Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = parent::handle($request, $next);

        $this->setUserData(auth()->user());

        return $response;
    }

    protected function setUserData($authUser)
    {
        if (!$authUser instanceof Model) {
            return;
        }

        // If the user is a Laravel Eloquent model we try to extract some common fields from it
        $userData = [
            'id' => $authUser instanceof Authenticatable
                ? $authUser->getAuthIdentifier()
                : $authUser->getKey(),
            'email' => $authUser->getAttribute('email') ?? $authUser->getAttribute('mail'),
        ];

        try {
            /** @var \Illuminate\Http\Request $request */
            $request = app()->make('request');

            if ($request instanceof Request) {
                $ipAddress = $request->ip();

                if ($ipAddress !== null) {
                    $userData['ip_address'] = $ipAddress;
                }
            }
        } catch (BindingResolutionException $e) {
            // If there is no request bound we cannot get the IP address from it
        }

        Integration::configureScope(static function (Scope $scope) use ($userData): void {
            $scope->setUser(array_filter($userData));
        });
    }

    public function terminate(Request $request, $response): void
    {
        # Do nothing when the application is terminating
        return;
    }

    /**
     * @param Request $request
     * @param $response
     * @return void
     */
    public function parentTerminate(Request $request, $response)
    {
        parent::terminate($request, $response);
    }
}

