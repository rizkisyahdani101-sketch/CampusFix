<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->paginate(15);

        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(Request $request)
    {
        $this->authorize('create', User::class);

        return view('admin.users.create', [
            'roles' => $this->assignableRoles(),
            'teams' => Team::query()->orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
            'role' => ['required', Rule::in($this->assignableRoles())],
            'is_active' => ['boolean'],
            'team_ids' => ['exclude_unless:role,technician', 'required', 'array', 'min:1'],
            'team_ids.*' => ['integer', 'distinct', 'exists:teams,id'],
        ], [
            'team_ids.required' => 'Pilih minimal satu tim teknisi.',
            'team_ids.min' => 'Pilih minimal satu tim teknisi.',
            'team_ids.*.exists' => 'Tim teknisi yang dipilih tidak tersedia.',
        ]);

        $user = DB::transaction(function () use ($validated): User {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            if ($user->role === User::ROLE_TECHNICIAN) {
                $user->teams()->sync($validated['team_ids']);
            }

            return $user;
        });

        return redirect()->route('admin.users.index')
            ->with('success', 'Pengguna berhasil dibuat.');
    }

    /** @return list<string> */
    private function assignableRoles(): array
    {
        return auth()->user()->isSuperAdmin()
            ? User::getRoles()
            : array_values(array_diff(User::getRoles(), [User::ROLE_SUPER_ADMIN]));
    }
}
