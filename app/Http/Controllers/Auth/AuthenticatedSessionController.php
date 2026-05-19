<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request, AuditLogService $auditLog): RedirectResponse
    {
        try {
            $request->authenticate();
        } catch (ValidationException $exception) {
            $auditLog->record('login_failed', [
                'module' => 'Auth',
                'description' => 'Failed login attempt',
                'user_email' => (string) $request->input('email'),
                'request_data' => $request->only('email', 'remember'),
                'status_code' => 422,
            ]);

            throw $exception;
        }

        $request->session()->regenerate();

        $auditLog->record('login_succeeded', [
            'module' => 'Auth',
            'description' => 'User logged in',
            'user' => Auth::user(),
            'request_data' => $request->only('email', 'remember'),
            'status_code' => 302,
        ]);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request, AuditLogService $auditLog): RedirectResponse
    {
        $auditLog->record('logout', [
            'module' => 'Auth',
            'description' => 'User logged out',
            'user' => Auth::user(),
            'status_code' => 302,
        ]);

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
