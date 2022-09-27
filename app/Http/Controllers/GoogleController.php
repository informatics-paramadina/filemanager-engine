<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use PHPUnit\Exception;

class GoogleController extends Controller
{
    public function redirect()
    {
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

       $token = auth()->setTTL($googleUser->expiresIn)->login($user);
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
       ]);

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
