<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets only approved accounts through.
 *
 * LoginRequest already refuses unapproved users at the login form, but an existing session or a
 * remember-me cookie skips that form. This middleware re-checks the flag on every request, so an
 * account whose approval is revoked loses access on its next request, not on its next login.
 */
class EnsureUserIsApproved
{
    public const PENDING_MESSAGE = 'Vaš nalog čeka odobrenje administratora. Prijava će biti moguća čim administrator odobri nalog.';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->approved) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                abort(403, self::PENDING_MESSAGE);
            }

            return redirect()->route('login')->withErrors(['email' => self::PENDING_MESSAGE]);
        }

        return $next($request);
    }
}
