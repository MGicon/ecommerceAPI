<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HasAnyRoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle( Request $request, Closure $next, ...$roles )
    {
        foreach($roles as $role){
            if ($request->user->hasRole($role)){


                return $next($request);
            }
        }

        return response(["message" => "Unauthorized"], 403);
    }
}
