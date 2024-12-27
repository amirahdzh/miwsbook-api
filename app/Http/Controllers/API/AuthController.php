<?php

namespace App\Http\Controllers\API;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $roleUser = Role::where('name', 'user')->first();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $roleUser->id,
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            "message" => "Register berhasil",
            'token' => $token,
            'user' => $user,
        ], 201);
    }

    public function getUser()
    {
        $user = auth()->user();
        // Eager load profile dan role
        $currentUser = User::with(['profile', 'role'])->find($user->id);

        return response()->json([
            "message" => "berhasil mendapatkan user",
            "user" => [
                'id' => $currentUser->id,
                'name' => $currentUser->name,
                'email' => $currentUser->email,
                'role' => $currentUser->role ? $currentUser->role->name : 'Unknown Role',
                'profile' => $currentUser->profile,
                'created_at' => $currentUser->created_at,
                'updated_at' => $currentUser->updated_at,
            ]
        ]);
    }

    public function login(Request $request)
    {
        $credentials = request(['email', 'password']);

        if (!$token = auth()->attempt($credentials)) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'User invalid'
            ], 401);
        }

        $userData = User::with('role')->where('email', $request['email'])->first();

        $token = JWTAuth::fromUser($userData);

        return response()->json([
            "message" => "Login berhasil",
            'user' => [
                'id' => $userData->id,
                'name' => $userData->name,
                'email' => $userData->email,
                'email_verified_at' => $userData->email_verified_at,
                'role' => [
                    'id' => $userData->role->id,
                    'name' => $userData->role->name
                ],
                'created_at' => $userData->created_at,
                'updated_at' => $userData->updated_at,
            ],
            'token' => $token,
        ], 201);
    }

    public function logout()
    {
        Auth::guard('api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }
}
