<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:api']);
    }

    public function show(Request $request)
    {
        $user = User::with('profile')->where('id', auth('api')->id())->first();
        return response()->json($user);
    }
}
