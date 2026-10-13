<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any users.
     * Super Admin dan Admin dapat melihat semua user
     *
     * @param \App\Models\User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    /**
     * Determine whether the user can view a specific user.
     *
     * @param \App\Models\User $user
     * @param \App\Models\User $target
     * @return bool
     */
    public function view(User $user, User $target): bool
    {
        // Super Admin dan Admin dapat melihat semua user
        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return true;
        }

        // Coordinator dapat melihat user lain kecuali Super Admin
        if ($user->role === User::ROLE_COORDINATOR && $target->role !== User::ROLE_SUPER_ADMIN) {
            return true;
        }

        // Technician hanya bisa melihat profile sendiri
        if ($user->role === User::ROLE_TECHNICIAN && $user->id === $target->id) {
            return true;
        }

        // Member hanya bisa melihat profile sendiri
        if ($user->role === User::ROLE_MEMBER && $user->id === $target->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create users.
     * Hanya Super Admin yang dapat create user baru
     *
     * @param \App\Models\User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->role === User::ROLE_ADMIN;
    }

    /**
     * Determine whether the user can update a specific user.
     *
     * @param \App\Models\User $user
     * @param \App\Models\User $target
     * @return bool
     */
    public function update(User $user, User $target): bool
    {
        // Super Admin dapat mengupdate semua user
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Admin dapat mengupdate semua user kecuali Super Admin
        if ($user->isAdmin() && $target->role !== User::ROLE_SUPER_ADMIN) {
            return true;
        }

        // Coordinator dapat mengupdate user (tambahkan logika sesuai scope)
        if ($user->role === User::ROLE_COORDINATOR) {
            return $target->role !== User::ROLE_SUPER_ADMIN;
        }

        // Technician dan Member hanya bisa update profile sendiri
        if (in_array($user->role, [User::ROLE_TECHNICIAN, User::ROLE_MEMBER])) {
            return $user->id === $target->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete a specific user.
     *
     * @param \App\Models\User $user
     * @param \App\Models\User $target
     * @return bool
     */
    public function delete(User $user, User $target): bool
    {
        // Super Admin dapat hapus semua user
        if ($user->isSuperAdmin()) {
            return $target->role !== User::ROLE_SUPER_ADMIN;
        }

        // Admin dapat hapus user kecuali Super Admin
        if ($user->isAdmin() && $target->role !== User::ROLE_SUPER_ADMIN) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore a deleted user.
     *
     * @param \App\Models\User $user
     * @param \App\Models\User $target
     * @return bool
     */
    public function restore(User $user, User $target): bool
    {
        return $this->delete($user, $target);
    }

    /**
     * Determine whether the user can permanently delete a user.
     *
     * @param \App\Models\User $user
     * @param \App\Models\User $target
     * @return bool
     */
    public function forceDelete(User $user, User $target): bool
    {
        return $this->delete($user, $target);
    }
}
