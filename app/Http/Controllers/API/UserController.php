<?php

namespace App\Http\Controllers\API;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    // Fungsi untuk mendapatkan semua pengguna
    public function getAllUsers()
    {
        $users = User::all();
        return response()->json($users);
    }

    // Fungsi untuk mendapatkan pengguna berdasarkan role
    public function getUsersByRole($roleId)
    {
        $users = User::where('role_id', $roleId)->get();
        $role = Role::findOrFail($roleId);
        return response()->json(['users' => $users, 'roleName' => $role->name]);
    }

     // Update user
     public function updateUser(Request $request, $id)
     {
         $validator = Validator::make($request->all(), [
             'name' => 'sometimes|required|string|max:255',
             'email' => 'sometimes|required|email|max:255',
             'role_id' => 'sometimes|required|exists:roles,id', // Validasi role_id
         ]);
 
         if ($validator->fails()) {
             return response()->json(['errors' => $validator->errors()], 422);
         }
 
         $user = User::findOrFail($id);
         
         // Cek dan update hanya field yang diberikan
         if ($request->has('name')) {
             $user->name = $request->input('name');
         }
         if ($request->has('email')) {
             $user->email = $request->input('email');
         }
         if ($request->has('role_id')) {
             $user->role_id = $request->input('role_id');
         }
 
         $user->save();
 
         return response()->json(['message' => 'User updated successfully', 'user' => $user]);
     }

    // Delete user
    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
}
