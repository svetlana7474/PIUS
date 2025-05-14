<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BlowfishController extends Controller
{
    public function validApi(Request $request)
    {
        return response()->json([
            'status' => 'ok',
            'message' => 'Контроллер работает!',
            'user_id' => $request->user_id,
            'action' => $request->action,
            'path' => $request->path,
            

            
            
        ]);
    }
    
}

