<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

class Authenticate
{
    public function handle(Request $request, Closure $next, ...$guards)
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (auth()->guard($guard)->check()) {
                return $next($request);
            }
        }
        
        
        throw new AuthenticationException('Unauthenticated.');
    }
}
