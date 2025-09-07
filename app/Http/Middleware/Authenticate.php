<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        // For API routes, don't redirect - let the middleware handle JSON response
        if ($request->is('api/*')) {
            return null;
        }
        
        // For web routes, redirect to home if no login route exists
        return '/';
    }
}
