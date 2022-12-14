<?php

namespace App\Http\Controllers;

use App\Models\AllowedEmail;
use App\Models\File;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use PHPUnit\Exception;
use Tymon\JWTAuth\Facades\JWTAuth;

class GoogleController extends Controller
{
    public function redirect(Request $request)
    {
        if($request->has('redirect_url'))
        {
            Session::put('redirect_url', $request->input('redirect_url'));
        }

        try {
            return Socialite::driver('google')->redirect();
        } catch (InvalidStateException $exception)
        {
            return response()->json(["error" => "invalid request"], 400);
        }
    }

    public function callback()
    {
       try {
           $googleUser = Socialite::driver('google')->user();
       } catch (Exception $exception)
       {
           return response()->json(['error' => 'invalid google response'], 401);
       }

       //find group
        $group = AllowedEmail::where('group', explode("@", $googleUser->getEmail())[1])->first();
       if(!$group)
       {
           return response()->json(['error' => 'forbidden access'], 401);
       }

        if(!$group->is_allowed)
        {
            return response()->json(['error' => 'forbidden access',
                'message' => 'your domain group ('.$group->group.') is blacklisted, please contact your administrator for further information'], 401);
        }

       $found = AllowedEmail::where('email', $googleUser->getEmail())->first();

       if(!$found && !$group)
       {
           return response()->json(['error' => 'forbidden access'], 401);
       }

       if(!$found && $group)
       {
           return response()->json(['error' => 'forbidden access',
               'message' => 'your account does not have access in '.($group->tag ?? $group->group).', please contact your administrator'], 401);
       }

       try {
           $user = User::updateOrCreate([
               'uid' => $googleUser->getId(),
               'email' => $googleUser->getEmail()
           ], [
               'password' => Hash::make($googleUser->getId())
           ]);
       } catch (InvalidStateException $exception)
       {
           return response()->json(['error' => 'cannot create user'], 500);
       }

       Profile::updateOrCreate([
           'user_id' => $user->id,
       ], [
           'name' => $googleUser->getName(),
           'avatar' => $googleUser->getAvatar(),
           'additionalInfo' => $googleUser->getId()
       ]);
       $token = JWTAuth::fromUser($user);
       if(!Storage::disk('local')->exists('/files/'.$user->uid))
       {
           Storage::disk('local')->makeDirectory('/files/'.$user->uid);
       }

       $file = File::updateOrCreate([
           'owned_by' => $user->id,
       ], [
           'filename' => $user->uid,
           'mime_type' => 'directory',
           'is_private' => true,
           'password' => Hash::make($user->uid),
           'location' => '/files/'.$user->uid,
           'is_user_root_folder' => true,
       ]);

       if(Session::has('redirect_url'))
       {
           $redirurl = Session::get('redirect_url');
           Session::forget('redirect_url');
           return redirect($redirurl."?token=".$token."&expiredIn=".$googleUser->expiresIn);
       }

       return $this->respondWithToken($token);
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'login_type' => 'google',
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL()
        ]);
    }
}
