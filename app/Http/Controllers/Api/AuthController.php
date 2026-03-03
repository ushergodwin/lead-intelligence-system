<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Issue a token for mobile / external clients.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'       => ['required', 'email'],
            'password'    => ['required', 'string'],
            'device_name' => ['sometimes', 'string', 'max:100'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $deviceName = $request->device_name ?? 'api-client';

        // Revoke any existing token with the same device name
        $user->tokens()->where('name', $deviceName)->delete();

        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'token'      => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->roles->first()?->name,
            ],
        ]);
    }

    /**
     * Revoke the current token (mobile logout).
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * Return the authenticated user with their role.
     */
    public function me(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        return response()->json([
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->roles->first()?->name,
        ]);
    }

    /**
     * List all personal access tokens for the authenticated user.
     */
    public function tokens(Request $request): JsonResponse
    {
        $tokens = $request->user()->tokens()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($t) => [
                'id'         => $t->id,
                'name'       => $t->name,
                'last_used'  => $t->last_used_at?->diffForHumans() ?? 'Never',
                'created_at' => $t->created_at->format('d M Y'),
            ]);

        return response()->json($tokens);
    }

    /**
     * Create a named personal access token (for mobile / API integrations).
     */
    public function createToken(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        /** @var \App\Models\User $user */
        $user  = $request->user();
        $token = $user->createToken($request->name);

        return response()->json([
            'message'    => 'Token created. Copy it now — it will not be shown again.',
            'token'      => $token->plainTextToken,
            'token_type' => 'Bearer',
            'id'         => $token->accessToken->id,
            'name'       => $request->name,
        ], 201);
    }

    /**
     * Revoke a specific token by ID.
     */
    public function revokeToken(Request $request, int $tokenId): JsonResponse
    {
        $deleted = $request->user()->tokens()->where('id', $tokenId)->delete();

        if (! $deleted) {
            return response()->json(['message' => 'Token not found.'], 404);
        }

        return response()->json(['message' => 'Token revoked.']);
    }
}
