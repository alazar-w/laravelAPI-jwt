<?php

namespace App\Http\Controllers;

use App\Meeting;
use App\User;
use Illuminate\Http\Request;
use JWTAuth;

class RegistrationController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $this->validate($request,[
            'meeting_id' => 'required',
            'user_id' => 'required'
        ]);
       $meeting_id = $request->input('meeting_id');

       $meeting = Meeting::findOrFail($meeting_id);

        if (!$user = JWTAuth::parseToken()->authenticate()){
            return response()->json(['msg'=> 'User not found'],401);
        }

       $message = [
           'msg' => 'User is already registered for meeting',
           'meeting' => $meeting,
           'user' => $user,
           'unregister' => [
               'href' =>'api/v1/meeting/registration/'.$meeting->id,
               'method' => 'GET'
           ]
       ];

       if($meeting->users()->where('user_id',$user->id)->first()){
           return response()->json($message,404);
       }
       $user->meetings()->attach($meeting);

       $response = [
           'msg' => 'User registered for meeting',
           'meeting' => $meeting,
           'user' => $user,
           'unregister' => [
               'href' =>'api/v1/meeting/registration/'.$meeting->id,
               'method' => 'GET'
           ]
       ];
        return response()->json($response,201);
    }



    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $meeting = Meeting::findOrFail($id);

        if (!$user = JWTAuth::parseToken()->authenticate()){
            return response()->json(['msg'=> 'User not found'],404);
        }

        if (!$meeting->users()->where('user_id',$user->id)->first()){
            return response()->json(['msg'=>'user not registered for meeting,delete not successful'],401);
        };

        $meeting->users()->detach($user->id);



        $response = [
            'msg' => 'User Unregisterd for meeting',
            'meeting' => $meeting,
            'user' => $user,
            'register' => [
                'href' =>'api/v1/meeting/registration',
                'method' => 'POST',
                'params' => 'user_id,meeting_id'
            ]
        ];

        return response()->json($response,200);

    }
}
