<?php

namespace App\Http\Controllers;

use App\Models\AllowedEmail;
use App\Models\File;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login()
    {
        $credentials = \request(['email', 'password']);
        //find group
        $group = AllowedEmail::where('group', explode("@", $credentials["email"])[1])->first();

        if($group && !$group->is_allowed)
        {
            return response()->json(['error' => 'forbidden access',
                'message' => 'your domain group ('.$group->group.') is blacklisted, please contact your administrator for further information'], 401);
        }

        $found = AllowedEmail::where('email', $credentials["email"])->first();

        if(!$found && !$group)
        {
            return response()->json(['error' => 'forbidden access'], 401);
        }



        if(!$found && $group)
        {
            return response()->json(['error' => 'forbidden access',
                'message' => 'your account does not have access in '.($group->tag ?? $group->group).', please contact your administrator'], 401);
        }


        if(!$token = auth()->setTTL(86400)->attempt($credentials))
        {
            return response()->json("unauthorized", 401);
        }

        return $this->respondWithToken($token);
    }
    protected function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }


    public function register(Request $request)
    {
        if(!$auth = $request->header('Authorization'))
        {
            return response()->json("unauthorized", 401);
        }

        if(!$request->has('email') || !$request->has('password'))
        {
            return response()->json("email or password required", 401);
        }

        try {
            $base64 = explode(" ",$auth)[1];
            $plain = explode(":", base64_decode($base64));

            if($plain[0] !== "ghifari" || $plain[1] !== "Gh1f4r1F1l3m4n4g3r"){
                return response()->json("unauthorized", 401);
            }

        } catch (\Exception $e)
        {
            return response()->json("unauthorized", 401);
        }

        try {
            $user = User::create([
                'uid' => $this->generateRandomString(),
                'email' => $request->email,
                'password' => Hash::make($request->password)
            ]);
            $profile = Profile::create([
                'user_id' => $user->id,
                'name' => $request->input('name', '-'),
                'avatar' => $request->input('avatar', "https://ui-avatars.com/api/?name=".($request->name ?? $user->email)."&background=random"),
            ]);
        } catch (\Exception $exception)
        {
            return response()->json($exception->getMessage());
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
        Storage::disk('local')->makeDirectory('/files/'.$user->uid);

        return response()->json($user);

    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'login_type' => 'email',
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }

    public function whitelistUser(Request $request)
    {
        if(!$auth = $request->header('Authorization'))
        {
            return response()->json("unauthorized", 401);
        }

        try {
            $base64 = explode(" ",$auth)[1];
            $plain = explode(":", base64_decode($base64));

            if($plain[0] !== "ghifari" || $plain[1] !== "Gh1f4r1F1l3m4n4g3r"){
                return response()->json("unauthorized", 401);
            }

        } catch (\Exception $e)
        {
            return response()->json("unauthorized", 401);
        }

        $validate = Validator::make($request->all(), [
            'email' => 'required'
        ]);

        if($validate->fails())
        {
            return response()->json($validate->errors(), 400);
        }

        $data = AllowedEmail::updateOrCreate([
            'email' => $request->email
        ], [
            'tag' => $request->tag,
            'group' => $request->group,
            'is_allowed' => $request->is_allowed ?? true
        ]);
        return response()->json($data);
    }
}
