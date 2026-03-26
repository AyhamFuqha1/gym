<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $sub = $user->subscription()->where('status', 'active')->first();
        if (!$sub) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sorry, your subscription has expired or is inactive. Please renew to access the gym.',
                'expiry_date' => $sub ? $sub->end_date : null
            ], 403); 
        }
        return $next($request);
    }
}
