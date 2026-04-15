<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserManagementController extends Controller
{
    /** Seeded primary admin — hidden from the user list (single built-in account). */
    private const DEFAULT_ADMIN_EMAIL = 'admin@admin.com';

    public function index(): JsonResponse
    {
        $users = User::query()
            ->select(['id', 'name', 'email', 'role', 'field_office', 'created_at'])
            ->where('email', '!=', self::DEFAULT_ADMIN_EMAIL)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (User $user) => $this->transformUser($user));

        return response()->json([
            'data' => $users,
        ]);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json([
            'data' => $this->transformUser($user),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(
            [
                'name' => ['required', 'string', 'max:255'],
                'username' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9@._-]+$/', 'unique:users,email'],
                'role' => ['nullable', 'string', 'in:admin'],
                'field_office' => ['nullable', 'string', 'max:100'],
                'password' => ['required', 'string', 'max:255', Password::min(8)->letters()->numbers()],
            ],
            [
                'username.regex' => 'The username may only contain letters, numbers, dots, underscores, hyphens, and @.',
            ],
        );

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['username'],
            'role' => $data['role'] ?? 'admin',
            'field_office' => $data['field_office'] ?? null,
            'password' => Hash::make($data['password']),
        ]);

        $this->logAction($request, 'CREATE', $user, null, $this->transformUser($user));

        return response()->json([
            'data' => $this->transformUser($user),
            'message' => 'User created successfully.',
        ], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $oldValues = $this->transformUser($user);

        $data = $request->validate(
            [
                'name' => ['required', 'string', 'max:255'],
                'username' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9@._-]+$/', 'unique:users,email,'.$user->id],
                'role' => ['nullable', 'string', 'in:admin'],
                'field_office' => ['nullable', 'string', 'max:100'],
                'password' => [
                    'nullable',
                    'string',
                    'max:255',
                    Rule::when($request->filled('password'), Password::min(8)->letters()->numbers()),
                ],
            ],
            [
                'username.regex' => 'The username may only contain letters, numbers, dots, underscores, hyphens, and @.',
            ],
        );

        $updatePayload = [
            'name' => $data['name'],
            'email' => $data['username'],
            'role' => $data['role'] ?? 'admin',
            'field_office' => $data['field_office'] ?? null,
        ];

        if (! empty($data['password'])) {
            $updatePayload['password'] = Hash::make($data['password']);
        }

        $user->update($updatePayload);
        $fresh = $user->fresh();

        $this->logAction($request, 'UPDATE', $fresh, $oldValues, $this->transformUser($fresh));

        return response()->json([
            'data' => $this->transformUser($fresh),
            'message' => 'User updated successfully.',
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->email === self::DEFAULT_ADMIN_EMAIL) {
            return response()->json([
                'message' => 'Default admin account cannot be deleted.',
            ], 422);
        }

        $oldValues = $this->transformUser($user);
        $userId = $user->id;
        $user->delete();

        ActivityLog::query()->create([
            'user_id' => $request->user()?->id,
            'action_type' => 'DELETE',
            'table_name' => 'users',
            'record_id' => $userId,
            'description' => 'User DELETE operation',
            'old_values' => $oldValues,
            'new_values' => null,
            'action_time' => now(),
        ]);

        return response()->json([
            'message' => 'User deleted successfully.',
        ]);
    }

    private function transformUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->email,
            'email' => $user->email,
            'role' => $user->role ?? ($user->email === 'admin@admin.com' ? 'admin' : 'system'),
            'field_office' => $user->field_office,
            'created_at' => optional($user->created_at)->toISOString(),
        ];
    }

    private function logAction(Request $request, string $action, User $user, ?array $oldValues, ?array $newValues): void
    {
        ActivityLog::query()->create([
            'user_id' => $request->user()?->id,
            'action_type' => $action,
            'table_name' => 'users',
            'record_id' => $user->id,
            'description' => "User {$action} operation",
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'action_time' => now(),
        ]);
    }
}
