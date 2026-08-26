<?php

namespace App\Http\Controllers;

use App\Http\Middleware\RequireAdminSession;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Passphrase login (R7). A prototype gate: one shared passphrase, no user accounts.
 */
class AuthController extends Controller
{
    public const MAX_ATTEMPTS = 5;

    public function show(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['passphrase' => ['required', 'string']]);

        $key = 'login:'.$request->ip();

        if (config('catalogue.admin_passphrase') !== (string) $request->input('passphrase')) {
            return back()->withErrors(['passphrase' => 'The passphrase is incorrect.']);
        }

        $request->session()->put(RequireAdminSession::SESSION_KEY, true);

        return redirect('/products');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget(RequireAdminSession::SESSION_KEY);
        $request->session()->regenerate();

        return redirect('/login');
    }
}
