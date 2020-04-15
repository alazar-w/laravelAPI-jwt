<?php

namespace App\Http\Controllers;

use App\User;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Exceptions\JWTException;
use JWTAuth;

class AuthController extends Controller
{

    public function store(Request $request)
    {
        $this->validate($request,[
           'name' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:5'
        ]);

        $name = $request->input('name');
        $email = $request->input('email');
        $password = $request->input('password');
        $hashed = Hash::make($password);

        $user = new User([
            'name' => $name,
            'email' => $email,
            'password' => $hashed
        ]);

        if ($user->save()){
            //since signin property was not created before it will be created for me.
            $user->signin = [
                'href' => 'api/v1/user/signin',
                'method' => 'POST',
                'params'=> 'email,password'
            ];
            $response = [
                'msg' =>'User Created',
                'User' => $user
            ];

            return response()->json($response,201);

        }


        $response = [
            'msg' =>'An error occurred',
        ];
        return response()->json($response,404);
    }





    public function signin(Request $request)
    {
        $this->validate($request,[
            'email' => 'required|email',
            'password' => 'required'
        ]);

//        $email = $request->input('email');
//        $password = $request->input('password');

        $credentials = $request->only('email','password');

        try{
            //attempt - means if attempting to login
            if(!$token = JWTAuth::attempt($credentials)){
                return response()->json(['msg'=>'Invalid credentials'],401);
            }

        }catch (JWTException $e){
            return response()->json(['msg' => 'Could not create token'],500);


    }


        return response()->json(['token'=> $token]);

    }



}
