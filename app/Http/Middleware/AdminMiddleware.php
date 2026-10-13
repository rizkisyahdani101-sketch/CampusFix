<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     * Member tidak boleh mengakses halaman admin
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

        // Hanya admin dan super admin yang boleh mengelola data sistem.
        $allowedRoles = ['admin', 'super_admin'];

        if (!in_array($user->role, $allowedRoles) && !$user->isSuperAdmin()) {
            \Log::warning('Admin page unauthorized access', [
                'user_id' => $user->id,
                'role' => $user->role,
            ]);

            abort(403, 'Halaman admin hanya dapat diakses oleh Admin, Coordinator, atau Super Admin.');
        }

        return $next($request);
    }
}
