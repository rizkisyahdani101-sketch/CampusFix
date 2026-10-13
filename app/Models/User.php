<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $attributes = [
        'role' => 'member',
        'is_active' => true,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'student_id',
        'email',
        'password',
        'role',
        'phone',
        'avatar',
        'department',
        'position',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Define roles as constant for easy access
     */
    const ROLE_MEMBER = 'member';
    const ROLE_TECHNICIAN = 'technician';
    const ROLE_COORDINATOR = 'coordinator';
    const ROLE_ADMIN = 'admin';
    const ROLE_SUPER_ADMIN = 'super_admin';

    /**
     * Get all available roles
     *
     * @return array
     */
    public static function getRoles(): array
    {
        return [
            self::ROLE_MEMBER,
            self::ROLE_TECHNICIAN,
            self::ROLE_COORDINATOR,
            self::ROLE_ADMIN,
            self::ROLE_SUPER_ADMIN,
        ];
    }

    /**
     * Get every role that works on reports (all roles except member).
     *
     * @return array
     */
    public static function staffRoles(): array
    {
        return array_values(array_diff(self::getRoles(), [self::ROLE_MEMBER]));
    }

    /**
     * Check if user has specific role
     *
     * @param string $role
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if user has any of the given roles
     *
     * @param array $roles
     * @return bool
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /**
     * Scope query by role
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $role
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_user')->withTimestamps();
    }

    /**
     * Check if user is super administrator
     *
     * @return bool
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    /**
     * Check if user is administrator
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_SUPER_ADMIN]);
    }

    /**
     * Check if user is a member
     *
     * @return bool
     */
    public function isMember(): bool
    {
        return $this->role === self::ROLE_MEMBER;
    }

    /**
     * Check if user is technician
     *
     * @return bool
     */
    public function isTechnician(): bool
    {
        return $this->role === self::ROLE_TECHNICIAN;
    }

    /**
     * Check if user is coordinator
     *
     * @return bool
     */
    public function isCoordinator(): bool
    {
        return $this->role === self::ROLE_COORDINATOR;
    }

    /**
     * Check if user is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    /**
     * Get role badge color
     *
     * @return string
     */
    public function getRoleBadgeAttribute(): string
    {
        $colors = [
            self::ROLE_MEMBER => 'primary',
            self::ROLE_TECHNICIAN => 'success',
            self::ROLE_COORDINATOR => 'warning',
            self::ROLE_ADMIN => 'info',
            self::ROLE_SUPER_ADMIN => 'danger',
        ];

        return $colors[$this->role] ?? 'secondary';
    }

    /**
     * Get role label
     *
     * @return string
     */
    public function getRoleLabelAttribute(): string
    {
        $labels = [
            self::ROLE_MEMBER => 'Member',
            self::ROLE_TECHNICIAN => 'Technician',
            self::ROLE_COORDINATOR => 'Coordinator',
            self::ROLE_ADMIN => 'Admin',
            self::ROLE_SUPER_ADMIN => 'Super Admin',
        ];

        return $labels[$this->role] ?? ucfirst($this->role);
    }

    /**
     * Public URL of the uploaded profile photo, if there is one.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        $avatar = is_string($this->avatar) ? trim($this->avatar) : '';

        return $avatar === '' ? null : asset('storage/'.$avatar);
    }

    /**
     * Initials used when the account has no profile photo.
     */
    public function getInitialsAttribute(): string
    {
        return (string) collect(explode(' ', trim((string) $this->name)))
            ->filter()
            ->take(2)
            ->map(static fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
            ->join('');
    }
}
