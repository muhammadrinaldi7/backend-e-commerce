<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
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
                'password' => 'required|string|min:6',
                'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            if($request->hasFile('avatar')) {
                $avatarPath = $request->file('avatar')->store('avatars', 'public');
            } else {
                $avatarPath = null; 
            }
            
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'avatar' => $avatarPath,
            ]);

            return response()->json([
                'token' => $user->createToken('api-token')->plainTextToken,
                'user' => $user,
            ]);
    }

    public function login(Request $request)
    {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            if (!Auth::attempt($request->only('email', 'password'))) {
                return response()->json(['message' => 'Invalid credentials'], 401);
            }

            $user = User::where('email', $request->email)->first();
            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }
            return response()->json([
                'token' => $user->createToken('api-token')->plainTextToken,
                'user' => $user,
            ]);
    }

    public function promoteToAdmin(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return ResponseHelper::error('User not found', 404);
        }
        if ($user->is_admin) {
            return ResponseHelper::error('User is already an admin', 400);
        }
        $user->is_admin = true;
        $user->save();
        return ResponseHelper::success($user, 'User promoted to admin successfully', 201);
    }
    public function getAllUsers(){
        $User = User::get();
        if($User->isEmpty()){
            return ResponseHelper::error('No users found', 404);
        }
        return ResponseHelper::success($User, 'Users retrieved successfully', 200);
    }

    public function updateAvatar(Request $request, $id) {
        $user = User::find($id);
        if (!$user) {
            return ResponseHelper::error('User not found', 404);
        }
        if($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
        } else {
            $avatarPath = null; 
        }
        $user->avatar = $avatarPath;
        $user->save();
        return ResponseHelper::success($user, 'Avatar updated successfully', 200);   
    }
}
