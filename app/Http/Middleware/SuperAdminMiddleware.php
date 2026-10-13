<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SuperAdminMiddleware
{
    /**
     * Handle an incoming request.
     * Super Admin memiliki akses konfigurasi sistem
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        if (!$user->isSuperAdmin()) {
            \Log::warning('Super Admin page unauthorized access', [
                'user_id' => $user->id,
                'role' => $user->role,
            ]);

            abort(403, 'Halaman konfigurasi hanya dapat diakses oleh Super Admin.');
        }

        return $next($request);
    }
}