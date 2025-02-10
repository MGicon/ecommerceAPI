<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LoggedInMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        $user = auth('sanctum')->user();

        if (!$user) {
            return response(["message" => "Unauthorized"], 403);
        }

        if ($user->ban && $user->ban > now()) {
            return response(["message" => "User is banned until " . $user->ban], 403);
        }

        $request->merge(['user' => $user]);

        return $next($request);
    }
}
