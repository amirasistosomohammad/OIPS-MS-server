<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $username = (string) $request->input('username', '');
        $password = (string) $request->input('password', '');

        if ($username === '' || $password === '') {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 422);
        }

        $user = User::query()->where('email', trim($username))->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 422);
        }

        $user->tokens()->delete();

        $expiresAt = now()->addHours(8);
        $token = $user->createToken('admin-login', ['*'], $expiresAt)->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt->toAtomString(),
            'expires_in' => 8 * 60 * 60,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->email,
                'email' => $user->email,
                'role' => $user->role ?? ($user->email === 'admin@admin.com' ? 'admin' : 'system'),
                'field_office' => $user->field_office,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->email,
                'email' => $user->email,
                'role' => $user->role ?? ($user->email === 'admin@admin.com' ? 'admin' : 'system'),
                'field_office' => $user->field_office,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}
