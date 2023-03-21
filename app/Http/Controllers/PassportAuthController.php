<?php

namespace App\Http\Controllers;

// Library
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;

// Model
use App\Models\User;

// Request
use App\Http\Requests\PassportAuthRegisterRequest;

class PassportAuthController extends BaseController
{
    /**
     * Registration Req
     */
    public function register(PassportAuthRegisterRequest $request)
    {

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password)
        ]);


        $token = $user->createToken(Str::random(10))->accessToken;
        return response()->json(['token' => $token], HttpResponse::HTTP_OK);
    }

    /**
     * Login Req
     */
    public function login(Request $request)
    {
        $data = [
            'email' => $request->email,
            'password' => $request->password
        ];

        if (auth()->attempt($data)) {
            $token = auth()->user()->createToken(Str::random(10))->accessToken;
            return response()->json(['token' => $token], HttpResponse::HTTP_OK);
        } else {
            return response()->json(['error' => 'Unauthorised'], HttpResponse::HTTP_UNAUTHORIZED);
        }
    }

    public function userInfo()
    {

     $user = auth()->user();

     return response()->json(['user' => $user], HttpResponse::HTTP_OK);

    }
}
