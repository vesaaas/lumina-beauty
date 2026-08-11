<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerEmailIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_admin && ! $user->email_verified_at) {
            return redirect()
                ->route('verification.otp.show')
                ->with('status', 'Please verify your email before continuing.');
        }

        return $next($request);
    }
}
