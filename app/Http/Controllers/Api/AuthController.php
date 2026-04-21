<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
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

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

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

        $token = auth('api')->login($user);

        return $this->respondWithToken($token);
    }

    public function me(Request $request)
    {
        $user = auth('api')->user();
        return response()->json([
            'success'=> true,
            'message'=> 'user retrieved successfully',
            'data' => $user,
        ]);
    }

    public function logout()
    {
        auth('api')->logout();
        return response()->json([
            'success'=> true,
            'message'=> 'Successfully logged out',
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
                'expires_in' => auth('api')->factory()->getTTL() * 60,
            ],
        ]);
    }
}
