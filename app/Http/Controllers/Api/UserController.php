<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\UserInvitationMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $sendInvite = (bool) $request->input('send_invite', false);

        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users'],
            'password' => [$sendInvite ? 'nullable' : 'required', 'string', 'min:8'],
            'role'     => ['required', 'string', Rule::in(['super_admin', 'manager', 'viewer'])],
        ]);

        if ($sendInvite) {
            $user = User::create([
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'password' => Hash::make(Str::random(32)),
            ]);
            $user->assignRole($validated['role']);

            $token = Password::createToken($user);
            Mail::to($user)->queue(new UserInvitationMail($user, $token, $validated['role']));

            $message = "Invitation sent to {$user->email}.";
        } else {
            $user = User::create([
                'name'              => $validated['name'],
                'email'             => $validated['email'],
                'password'          => Hash::make($validated['password']),
                'email_verified_at' => now(),
            ]);
            $user->assignRole($validated['role']);

            $message = "User {$user->name} created successfully.";
        }

        return response()->json([
            'message' => $message,
            'user'    => [
                'id'                => $user->id,
                'name'              => $user->name,
                'email'             => $user->email,
                'role'              => $validated['role'],
                'created_at'        => $user->created_at->format('d M Y'),
                'email_verified_at' => $user->email_verified_at,
            ],
        ], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role'     => ['required', 'string', Rule::in(['super_admin', 'manager', 'viewer'])],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $user->update([
            'name'  => $validated['name'],
            'email' => $validated['email'],
            ...($validated['password'] ? ['password' => Hash::make($validated['password'])] : []),
        ]);

        $user->syncRoles([$validated['role']]);

        return response()->json([
            'message' => "User {$user->name} updated.",
            'user'    => [
                'id'                => $user->id,
                'name'              => $user->name,
                'email'             => $user->email,
                'role'              => $validated['role'],
                'created_at'        => $user->created_at->format('d M Y'),
                'email_verified_at' => $user->email_verified_at,
            ],
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'You cannot delete your own account from here.'], 422);
        }

        $name = $user->name;
        $user->delete();

        return response()->json(['message' => "{$name} has been removed."]);
    }

    public function resendInvite(User $user): JsonResponse
    {
        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'This user has already activated their account.'], 422);
        }

        $role  = $user->roles->first()?->name ?? 'viewer';
        $token = Password::createToken($user);
        Mail::to($user)->queue(new UserInvitationMail($user, $token, $role));

        return response()->json(['message' => "Invitation resent to {$user->email}."]);
    }
}
