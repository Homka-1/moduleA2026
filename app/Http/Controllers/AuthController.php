<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'username' => 'required|string|unique:users|min:3',
            'password' => 'required|string|confirmed|min:6',
            'password_confirmation' => 'required|string|same:password',
        ]);

        $user = User::query()->create([
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'role' => 'USER'
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'role' => 'USER'
        ]);
    }
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string'
        ]);

        $user = User::query()->where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid login'], 422);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'role' => $user->role,
        ]);
    }

    public function logout(){
        auth()->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'logout success'], 200);
    }
}
