<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class FileController extends Controller
{

    public function __construct()
    {
        $this->middleware(['auth:api']);
    }

    public function index()
    {
        $files = File::with('owner:email,id')
            ->where('parent_id', null)
            ->get();

        return response()->json($files);
    }

    public function destroy(Request $request, string $uuid)
    {
        $files = File::find($uuid);
        if(!$files) return response()->json("not found", 404);

    }

    public function download(Request $request, string $uuid)
    {
        $file = File::find($uuid);

        if(!$file) return response()->json("not found", 404);

        if($file->mime_type == "directory") return response()->json("cannot download directory", 400);

        if($file->is_private)
        {
            if($file->password && $request->has('password') && $file->password == $request->password)
            {
                return response()->download(storage_path("/app".$file->location), $file->filename, ["Content-Type: ".$file->mime_type]);
            }

            if($file->owned_by != auth()->user()->id) return response()->json([
                "error" => "unauthorized",
                "message" => "you don't have access to this file/folder",
                "have_password" => (bool)$file->password,
            ], 401);
        }

        $content = Storage::disk("local")->get($file->location);
        return response()->download(storage_path("/app".$file->location), $file->filename, ["Content-Type: ".$file->mime_type]);
    }

    public function show(Request $request, string $uuid)
    {
        $files = File::with('children', 'parent', 'owner:email,id', 'permission')->whereIn('id', [$uuid])->first();

        if(!$files) return response()->json("not found", 404);

        if($files->is_private)
        {
            if($files->password && $request->has('password') && $files->password == $request->password)
            {
                return response()->json($files);
            }

            if($files->owned_by != auth()->user()->id) return response()->json([
                "error" => "unauthorized",
                "message" => "you don't have access to this file/folder",
                "have_password" => (bool)$files->password,
            ], 401);
        }

        // check if files has parent
        if($files->parent)
        {
            // check if folder parent is private
            if($files->parent->is_private)
            {
                if(!$request->has('password') || $request->password != $files->parent->password)
                {
                    return response()->json('unauthorized access to '.$files->id, 401);
                }
            }
        }

        return response()->json($files);
    }

    public function insert(Request $request)
    {
        $statusFile = [];
        $parentFile = null;

        if($request->has('parent_id'))
        {
            $parentFile = File::find($request->parent_id);
            if(!$parentFile) return response()->json("invalid parent_id", 400);
        }

        if(!$request->folder_name && !$request->hasFile('multifiles'))
        {
            return response()->json("multifiles or folder_name required", 400);
        }


        // reject if uuid mime_type is not directory
        if($parentFile &&  $parentFile->mime_type != "directory") return response()->json("file cannot be parent of other file, only directory", 400);


        if(!$request->hasFile('multifiles'))
        {
            File::create([
                'filename' => $request->folder_name,
                'mime_type' => 'directory',
                'size' => 0,
                'owner' => $request->owner,
                'owned_by' => auth()->user()->id,
                'parent_id' => $request->parent_id ,
                'is_private' => $request->is_private ?? false,
                'password' => $request->password,
            ]);

            return  response()->json("folder created");
        }

        $files = $request->file('multifiles');

        foreach ($files as $file)
        {
            $location = "/files/".auth()->user()->uid."/".$file->getClientOriginalName();
            $newFile = File::create([
                'filename' => $file->getClientOriginalName(),
                'extension' => $file->extension(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'owner' => $request->owner,
                'owned_by' => auth()->user()->id,
                'parent_id' => $request->parent_id ,
                'is_private' => $request->is_private ?? false,
                'password' => $request->password,
                'location' => $location,
                ]);

            Storage::disk('local')->put($location ,$file->getContent());
            $statusFile[] = ['filename' => $file->getClientOriginalName(), 'status' => 'success'];
        }

        return  response()->json($statusFile);


    }
}
