<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $credentials = $request->only('email','password');

        if (!Auth::attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('ChatApp')->accessToken;

        return $this->respondWithToken($token);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email'=> 'required|email|unique:users',
            'password'=> 'required',
            'password_confirmation' => 'required|same:password',
            ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        $token = $user->createToken('ChatApp')->accessToken;

        return $this->respondWithToken($token);
    }

    public function me(Request $request)
    {
        return response()->json([
            'success'=> true,
            'message'=> 'user retrieved successfully',
            'data' => $request->user(),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return response()->json([
            'success'=> true,
            'message'=> 'Successfully logged out',
        ]);
    }

    public function users(Request $request)
    {
        $users = User::where('id', '!=', $request->user()->id)->get();
        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'success'=> true,
            'message'=> 'token retrieved successfully',
            'data' => [
                'access_token' => $token,
                'token_type' => 'bearer',
            ],
        ]);
    }
}
