<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function store (Request $request)
    {
        $validator = Validator::make($request->all(), [
            'age' => 'required|integer',
            'biodata' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $currentUser = auth()->user();

        $user = User::find($currentUser->id);

        $profileData = $user->profile()->updateOrCreate(
            ['user_id' => $user->id], // Kondisi untuk menentukan apakah harus mengupdate atau membuat baru
            [
                'age' => $request->input('age'),
                'biodata' => $request->input('biodata'),
            ]
        );
    
        // Mengembalikan respons berhasil
        return response()->json([
            'message' => 'Profile successfully updated or created',
            'profile' => $profileData,
        ], 200);
    }
}
