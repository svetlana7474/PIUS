<?php

namespace App\Http\Controllers;

use App\BlowfishCrypt\Blowfish;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BlowfishController extends Controller
{
    

    public function validApi(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'path' => 'required|string',
            'action' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 400,
                'message' => 'Incorrect input data',
            ], 400);
        }

        $userId = $request->query('user_id');
        $filePath = $request->query('path');
        $action = $request->query('action');

        $blowfish = new Blowfish();
        if (!file_exists($filePath)) {
            return response()->json([
                'code' => 404,
                'message' => 'File not found. Try again',
            ], 404);
        }

        if ($action === 'encrypt') {
            list($message,$status) = $blowfish->encryptFile($filePath,$userId);
            return response()->json([
                'code' => $status,
                'FilePath' => $message,
            ],$status);
        }
        else if ($action === 'decrypt') {
            list($message,$status) = $blowfish->decryptFile($filePath,$userId);
            return response()->json([
                'code' => $status,
                'FilePath' => $message,
            ],$status);
        } else {
            return response()->json([
                'code' => 400,
                'message' => 'Action is not recognized',
            ], 400);
        }
    }
}
