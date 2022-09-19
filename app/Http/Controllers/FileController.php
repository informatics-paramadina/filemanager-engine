<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class FileController extends Controller
{

    public function index()
    {
        $files = File::where('parent_id', null)->get();

        return response()->json($files);
    }

    public function show(Request $request, string $uuid)
    {
        $files = File::with('children', 'parent')->whereIn('id', [$uuid])->first();

        if(!$files) return response()->json("not found", 404);


        // check if user want access to folder
        if($files->mime_type == "directory" && $files->is_private)
        {
            if(!$request->has('password') || $request->password != $files->password)
            {
                return response()->json('unauthorized access to '.$files->id, 401);
            }
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
        if($parentFile->mime_type != "directory") return response()->json("file cannot be parent of other file, only directory", 400);


        if(!$request->hasFile('multifiles'))
        {
            File::create([
                'filename' => $request->folder_name,
                'mime_type' => 'directory',
                'size' => 0,
                'owner' => $request->owner,
                'parent_id' => $request->parent_id ,
                'is_private' => $request->is_private ?? false,
                'password' => $request->password,
            ]);

            return  response()->json("folder created");
        }

        $files = $request->file('multifiles');

        foreach ($files as $file)
        {
            $location = "/files/".$file->getClientOriginalName();
            $newFile = File::create([
                'filename' => $file->getClientOriginalName(),
                'extension' => $file->extension(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'owner' => $request->owner,
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
