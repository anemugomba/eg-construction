<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request): JsonResponse
    {
        // Only administrators can list users
        if ($request->user()->role !== User::ROLE_ADMINISTRATOR) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = User::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $query->withCount('sites');

        $sortBy = $request->get('sort_by', 'name');
        $sortDir = $request->get('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        $perPage = $request->get('per_page', 50);

        return response()->json($query->paginate($perPage));
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request): JsonResponse
    {
        // Only administrators can create users
        if ($request->user()->role !== User::ROLE_ADMINISTRATOR) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', Password::defaults()],
            'role' => ['required', Rule::in([
                User::ROLE_ADMINISTRATOR,
                User::ROLE_SENIOR_DPF,
                User::ROLE_SITE_DPF,
                User::ROLE_DATA_ENTRY,
                User::ROLE_VIEW_ONLY,
            ])],
            'notify_email' => 'boolean',
            'notify_sms' => 'boolean',
            'notify_whatsapp' => 'boolean',
            'phone_number' => 'nullable|string|max:20',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['email_verified_at'] = now();

        $user = User::create($validated);

        return response()->json([
            'message' => 'User created successfully',
            'data' => $user,
        ], 201);
    }

    /**
     * Display the specified user.
     */
    public function show(Request $request, User $user): JsonResponse
    {
        // Only administrators can view other users, users can view themselves
        if ($request->user()->role !== User::ROLE_ADMINISTRATOR && $request->user()->id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user->load('sites');
        $user->loadCount('sites');

        return response()->json([
            'data' => $user,
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        // Only administrators can update other users, users can update themselves (limited fields)
        $isAdmin = $request->user()->role === User::ROLE_ADMINISTRATOR;
        $isSelf = $request->user()->id === $user->id;

        if (!$isAdmin && !$isSelf) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $rules = [
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'email', Rule::unique('users')->ignore($user->id)],
            'notify_email' => 'boolean',
            'notify_sms' => 'boolean',
            'notify_whatsapp' => 'boolean',
            'phone_number' => 'nullable|string|max:20',
        ];

        // Only admins can change password and role
        if ($isAdmin) {
            $rules['password'] = ['sometimes', Password::defaults()];
            $rules['role'] = ['sometimes', Rule::in([
                User::ROLE_ADMINISTRATOR,
                User::ROLE_SENIOR_DPF,
                User::ROLE_SITE_DPF,
                User::ROLE_DATA_ENTRY,
                User::ROLE_VIEW_ONLY,
            ])];
        }

        $validated = $request->validate($rules);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'User updated successfully',
            'data' => $user->fresh(),
        ]);
    }

    /**
     * Remove the specified user.
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        // Only administrators can delete users
        if ($request->user()->role !== User::ROLE_ADMINISTRATOR) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Cannot delete yourself
        if ($request->user()->id === $user->id) {
            return response()->json([
                'message' => 'Cannot delete your own account',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }

    /**
     * Get sites assigned to a user.
     */
    public function sites(Request $request, User $user): JsonResponse
    {
        // Only administrators can view other users' sites
        if ($request->user()->role !== User::ROLE_ADMINISTRATOR && $request->user()->id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'data' => $user->sites,
        ]);
    }

    /**
     * Assign sites to a user.
     */
    public function assignSites(Request $request, User $user): JsonResponse
    {
        // Only administrators can assign sites
        if ($request->user()->role !== User::ROLE_ADMINISTRATOR) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'site_ids' => 'required|array',
            'site_ids.*' => 'exists:sites,id',
        ]);

        $user->sites()->sync($validated['site_ids']);

        return response()->json([
            'message' => 'Sites assigned successfully',
            'data' => $user->fresh()->load('sites'),
        ]);
    }

    /**
     * Remove a site from a user.
     */
    public function removeSite(Request $request, User $user, Site $site): JsonResponse
    {
        // Only administrators can remove site assignments
        if ($request->user()->role !== User::ROLE_ADMINISTRATOR) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user->sites()->detach($site->id);

        return response()->json([
            'message' => 'Site removed from user',
            'data' => $user->fresh()->load('sites'),
        ]);
    }
}
