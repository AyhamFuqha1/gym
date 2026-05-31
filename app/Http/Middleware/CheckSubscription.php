<?php

namespace App\Http\Middleware;

use App\Services\MemberService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    public function __construct(private MemberService $memberService)
    {
        //
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $access = $this->memberService->ensureMemberSubscriptionAccess($user);

        if (!($access['allowed'] ?? false)) {
            return response()->json(array_filter([
                'status' => 'error',
                'message' => $access['message'],
                'title' => $access['title'] ?? 'Subscription Required',
                'code' => $access['code'] ?? 'subscription_required',
                'subscription_status' => $access['subscription_status'] ?? null,
                'subscription_id' => $access['subscription_id'] ?? null,
                'renew_required' => $access['renew_required'] ?? true,
            ], fn ($value) => $value !== null), $access['status'] ?? 403);
        }

        return $next($request);
    }
}
