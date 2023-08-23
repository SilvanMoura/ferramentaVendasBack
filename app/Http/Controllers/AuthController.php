<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request)
    {
        $input = $request->validated();
        
        $credentials = [
            'email' => $input['email'],
            'password' => $input['password']
        ];

        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    public function register(RegisterRequest $request)
    {
        $input = $request->validated();
        
        $credentials = [
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password']
        ];

        $userExists = User::where([
            ['email', 'like', '%'.$credentials['email'].'%']
        ])->get();
        
        if( count($userExists) >= 1 ){
            return response()->json('failed');
        }

        $credentials['password'] = bcrypt($credentials['password']);
        User::create($credentials);

        return response()->json('success'); 
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }
}
