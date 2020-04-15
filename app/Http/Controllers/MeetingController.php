<?php

namespace App\Http\Controllers;

use App\Meeting;
use App\User;
use Carbon\Carbon;
use http\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use JWTAuth;

class MeetingController extends Controller
{
    //we apply our middleware here,we protect actions we only want not all actions of the controller with ("only keyword")
    public function __construct()
    {
        $this->middleware('jwt.auth',['only'=>[
            'update','store','destroy'
        ]]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $meetings = Meeting::all();
        foreach ($meetings as $meeting){
            $meeting->view_meeting = [
                'href' => 'api/v1/meeting/'.$meeting->id,
                'method' => 'GET'
            ];
        }


        $response = [
            'msg' => 'List of all Meeting',
            'meetings' =>[
                $meetings
            ]
        ];

        return response()->json($response,200);
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
            'title'=>'required',
            'description'=>'required',
            'time'=>'required|date_format:YmdHie',
        ]);

        //EXTRACTING USER FROM THE TOKEN
         if (!$user = JWTAuth::parseToken()->authenticate()){
             return response()->json(['msg'=> 'token not found'],404);
         }

        $title = $request->input('title');
        $description = $request->input('description');
        $time = $request->input('time');
        //we get the user id from the user object extracted from the token
        $user_id= $user->id;

        $meeting = new Meeting([
            'title' => $title,
            'description' => $description,
            'time' => Carbon::createFromFormat('YmdHie',$time),
        ]);

        if ($meeting->save()){
            $meeting->users()->attach($user_id);
            $meeting->view_meeting =[
                'href' => 'api/v1/meeting/'.$meeting->id,
                'method' => 'GET'
            ];
            $message = [
                'msg' => 'Meeting created',
                'meeting' => $meeting
            ];
            return response()->json($message,201);

        }

        $response = [
            'msg' => 'error has occurred',
        ];

        return response()->json($response,404);

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $meeting = Meeting::with('users')->where('id',$id)->firstOrFail();
        $meeting->view_meetings = [
            'href' => 'api/v1/meeting',
            'method' => 'GET'
        ];

        $response = [
            'msg' => 'Meeting information',
            'meeting' => $meeting
        ];

        return response()->json($response,200);

    }



    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request,[
            'title'=>'required',
            'description'=>'required',
            'time'=>'required|date_format:YmdHie',
        ]);

        if (!$user = JWTAuth::parseToken()->authenticate()){
            return response()->json(['msg'=> 'User not found'],404);
        }

        $title = $request->input('title');
        $description = $request->input('description');
        $time = $request->input('time');


        $meeting = Meeting::with('users')->findOrFail($id);

        if (!$meeting->users()->where('user_id',$user->id)->first()){
            return response()->json(['msg'=>'user not registered for meeting,update not successful'],401);
        };

        $meeting->time =Carbon::createFromFormat('YmdHie',$time);
        $meeting->title = $title;
        $meeting->description = $description;

        if (!$meeting ->update()){
            return response()->json(['msg'=>'Error during updating'],404);
        }
        $meeting->view_meeting = [
            'href' => 'api/v1/meeting/'.$meeting->id,
            'method' => 'GET'
        ];

        $response = [
            'msg' => 'Meeting updated',
            'meeting' => $meeting
        ];

        return response()->json($response,200);

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
            return response()->json(['msg'=>'user not registered for meeting,update not successful'],401);
        };

        $users = $meeting->users;

        //detach all the users to delete a meeting
        $meeting->users()->detach();

        if(!$meeting->delete()){
            foreach ($users as $user){

                //attach all the users back if the deletion fails
                $meeting->users()->attach($user);
            }
            return response()->json(['msg'=>'deletion failed'],404);

        }


        $response = [
            'msg' => 'Meeting deleted',
            'create' => [
                'href'=> 'api/v1/meeting',
                'method' =>'POST',
                'params' => 'title,description,time'
            ]
        ];

        return response()->json($response,200);

    }
}
