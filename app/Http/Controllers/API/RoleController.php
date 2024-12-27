<?php

namespace App\Http\Controllers\API;

use App\Models\Role;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:api', 'isOwner']);
    }

    /**
     * Display a listing of the roles.
     */
    public function index()
    {
        $roles = Role::all();

        return response()->json([
            'message' => 'Roles retrieved successfully',
            'data' => $roles,
        ]);
    }

    /**
     * Store a newly created role in storage.
     */
    public function store(Request $request)
    {
        // Validate data
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        // Create a new role
        $role = Role::create([
            'name' => $request->name,
        ]);

        return response()->json([
            "message" => "Role created successfully",
            "data" => $role,
        ], 201);
    }

    /**
     * Display the specified role.
     */
    public function show(string $id)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json([
                "message" => "Role not found",
            ], 404);
        }

        return response()->json([
            "message" => "Role details retrieved successfully",
            "data" => $role,
        ]);
    }

    /**
     * Update the specified role in storage.
     */
    public function update(Request $request, string $id)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json([
                "message" => "Role not found",
            ], 404);
        }

        // Prevent updating 'owner' and 'default role'
        if ($role->name === 'owner' || $role->name === 'default role') {
            return response()->json([
                "message" => "Cannot update this role",
            ], 403);
        }

        // Validate data
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        // Update role data
        $role->name = $request->name;
        $role->save();

        return response()->json([
            "message" => "Role updated successfully",
            "data" => $role,
        ]);
    }

    /**
     * Remove the specified role from storage.
     */
    public function destroy(string $id)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json([
                "message" => "Role not found",
            ], 404);
        }

        // Prevent deletion of 'owner' and 'default role'
        if ($role->name === 'owner' || $role->name === 'default role') {
            return response()->json([
                "message" => "Cannot delete this role",
            ], 403);
        }
        

        // Handle user reassignment to default role
        $defaultRole = Role::where('name', 'default role')->first();
        if ($defaultRole) {
            // Move users with the role being deleted to the default role
            $users = $role->users; // Assuming you have a relationship with users
            foreach ($users as $user) {
                $user->role_id = $defaultRole->id;
                $user->save();
            }
        }

        $role->delete();

        return response()->json([
            "message" => "Role deleted successfully",
        ]);
    }
}
