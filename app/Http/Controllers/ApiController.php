<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Group;
use App\Http\Requests\RegisterAuthRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Helpers\SuumaResponse;
use Illuminate\Support\Facades\Hash;

class ApiController extends Controller
{
    public $loginAfterSignUp = false;

    protected $auth;

    public function register(RegisterAuthRequest $request){

        $user = new User();
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->range = $request->range;
        $user->check_privacy = $request->check_privacy;

        $user->save();

        $profile = new Profile();
        $profile->name = $request->name;
        $profile->appat = $request->appat;
        $profile->apmat = $request->apmat;
        $user->profile()->save($profile);

        // Attach minimum permissions.
        $basic = Group::where('name', 'basic')->first();
        $user->groups()->sync($basic);

        if($this->loginAfterSignUp) {
            return $this->login($request);
        }

        return response()->json([
           'success' => true,
           'data' => $user,
           'groups' => $user->groups
        ], 200);

    }

    public function login(Request $request){

        $input = $request->only('email', 'password');
        $jwt_token = null;
        $res = null;

        if (!$jwt_token = JWTAuth::attempt($input)) {
            $res = new SuumaResponse(
                200,
                "Error",
                "user_wrong-user-password",
                401,
                "Usuario o contraseña incorrecto."
                );
            return response()->json($res->getResponse()[0]);
        }

        $user = Auth::user();

        // Add profile
        $user->profile = $user->profile;

        // Add groups
        $raw_groups = $user->groups;
        $groups = [];

        foreach ($raw_groups as $group) {
            array_push($groups, $group['name']);
        }

        $user->roles = $groups;

        if ($this->isUserActive(Auth::user())) {
            $res = new SuumaResponse(
                200,
                "OK",
                "",
                200,
                "Login Successful", [
                "token" => $jwt_token,
                "user" => $user
            ]);
            return response()->json($res->getResponse()[0]);
        }
        else {
            $res = new SuumaResponse(
                200,
                "Error",
                "user_not-active",
                401,
                "Usuario no activo.");
            return response()->json($res->getResponse()[0]);
        }


    }

    public function logout(Request $request){

        // TODO: Improve response for checking the token

        try {
            JWTAuth::invalidate($request->bearerToken());

            return response()->json([
                'success' => true,
                'message' => 'User logged out successfully'
            ], 200);

        } catch ( JWTException $exception ) {
            return response()->json([
               'success' => false,
               'message' => 'Sorry, user cannot be logged out'
            ], 500);
        }

    }

    public function refresh(Request $request){
        try {

            $jwt = JWTAuth::refresh($request->bearerToken());

            return response()->json([
                'success' => true,
                'message' => $jwt
            ], 200);

        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => $e
            ], 500);
        }
    }

    public function getAuthUser(Request $request) {

        $token = $request->bearerToken();

        $user = JWTAuth::authenticate($token);

        return response()->json([
            'data' => [
                'user' => $user
            ]
        ]);
    }

    public function welcome(Request $request){
        return response()->json(
            ['message' => 'Welcome to SUUMA API v1.0']
        );
    }

    protected function isUserActive($user){
        if ($user->isActive == 0){
            return false;
        }
        return true;
    }

    public function changePassword(Request $request) {
        $last = $request->last;
        $new = $request->new;

        $user = Auth::user();
        if (Hash::check($last, $user->password)) {
            $user->password = bcrypt($new);
            $user->save();

            $res = new SuumaResponse(
                200,
                'OK',
                '',
                200,
                "Contraseñas coinciden, se ha actualizado."
            );

            return response()->json($res->getResponse()[0]);
        }
            $res = new SuumaResponse(
                200,
                'ERROR',
                'Bad password',
                401,
                "Por favor revisar su contraseña pasada."
            );

            return response()->json($res->getResponse()[0]);
    }

}
