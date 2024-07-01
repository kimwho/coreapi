<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
class RoleandPermissionController extends Controller
{
    // Create a new role
    public function createRole(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name',
        ]);

        // Create the role with the specified name and guard_name
        $role = Role::create([
            'name' => $request->name,

        ]);

        return response()->json(['success' => true, 'role' => $role], 201);
    }

    // Get all roles
    public function getRoles()
    {
        $roles = Role::all();

        return response()->json(['success' => true, 'roles' => $roles], 200);
    }

    // Get a specific role by ID
    public function getRole($id)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json(['success' => false, 'message' => 'Role not found'], 404);
        }

        return response()->json(['success' => true, 'role' => $role], 200);
    }

    // Update a specific role by ID
    public function updateRole(Request $request, $id)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json(['success' => false, 'message' => 'Role not found'], 404);
        }

        $request->validate([
            'name' => 'required|string|unique:roles,name,' . $role->id,
        ]);

        $role->name = $request->name;
        $role->save();

        return response()->json(['success' => true, 'role' => $role], 200);
    }

    // Delete a specific role by ID
    public function deleteRole($id)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json(['success' => false, 'message' => 'Role not found'], 404);
        }

        $role->delete();

        return response()->json(['success' => true, 'message' => 'Role deleted successfully'], 200);
    }


    public function switchRole(Request $request)
    {
        $user = Auth::user();
        $newRole = $request->input('role');

        // Check if the user has the role they want to switch to
        if ($user->roles->contains('name', $newRole)) {
            // Store the active role in the session or user model
            $user->active_role = $newRole;
            $user->save();

            return response()->json(['message' => 'Role switched successfully', 'active_role' => $newRole], 200);
        } else {
            return response()->json(['message' => 'Invalid role'], 400);
        }
    }

    // Create a new permission
    public function createPermission(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:permissions,name',
        ]);

        $permission = Permission::create(['name' => $request->name]);

        return response()->json(['success' => true, 'permission' => $permission], 201);
    }

    // Get all permissions
    public function getPermissions()
    {
        $permissions = Permission::all();

        return response()->json(['success' => true, 'permissions' => $permissions], 200);
    }

    public function getPermission($id)
    {
        $permission = Permission::findById($id);

        if (!$permission) {
            return response()->json(['success' => false, 'message' => 'Permission not found'], 404);
        }

        return response()->json(['success' => true, 'permission' => $permission], 200);
    }

    public function updatePermission(Request $request, $id)
    {
        $permission = Permission::findById($id);

        if (!$permission) {
            return response()->json(['success' => false, 'message' => 'Permission not found'], 404);
        }

        $request->validate([
            'name' => 'required|string|unique:permissions,name,' . $id,
        ]);

        $permission->name = $request->name;
        $permission->save();

        return response()->json(['success' => true, 'permission' => $permission], 200);
    }

    public function deletePermission($id)
    {
        $permission = Permission::findById($id);

        if (!$permission) {
            return response()->json(['success' => false, 'message' => 'Permission not found'], 404);
        }

        $permission->delete();

        return response()->json(['success' => true, 'message' => 'Permission deleted successfully'], 200);
    }


// assign permission to a role wont affect old permisson of a role
    public function assignPermissionsToRole(Request $request, $roleId)
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role = Role::findById($roleId);

        if (!$role) {
            return response()->json(['success' => false, 'message' => 'Role not found'], 404);
        }

        foreach ($request->permissions as $permission) {
            $role->givePermissionTo($permission);
        }

        return response()->json(['success' => true, 'message' => 'Permissions assigned to role successfully.'], 200);
    }

    public function removePermissionFromRole(Request $request, $roleId)
    {
        $request->validate([
            'permission' => 'required|exists:permissions,name',
        ]);

        $role = Role::findById($roleId);

        if (!$role) {
            return response()->json(['success' => false, 'message' => 'Role not found'], 404);
        }

        $role->revokePermissionTo($request->permission);

        return response()->json(['success' => true, 'message' => 'Permission removed from role successfully.'], 200);
    }

    public function getUserRoles($userId)
    {
            $user = User::findOrFail($userId);
            $roles = $user->roles;

            return response()->json(['success' => true, 'roles' => $roles], 200);
    }

    public function getUserPermissions($userId)
    {
        $user = User::findOrFail($userId);
        $permissions = $user->getAllPermissions();

        return response()->json(['success' => true, 'permissions' => $permissions], 200);
    }

    // public function assignRolesToUser(Request $request)
    // {
    //     $request->validate([
    //         'user_id' => 'required|exists:users,id',
    //         'roles' => 'required|array',
    //         'roles.*' => 'exists:roles,name',
    //     ]);
    
    //     $user = User::findOrFail($request->user_id);
    //     foreach ($request->roles as $role) {
    //         $user->assignRole($role);
    //     }
    
    //     return response()->json(['success' => true, 'message' => 'Roles assigned successfully.'], 200);
    // }

    public function assignRolesToUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,name',
        ]);
    
        $user = User::findOrFail($request->user_id);
        $authUser = $request->user(); // The authenticated user performing the assignment
    
        $newRoles = [];
        $roles = $request->roles;
    
        foreach ($roles as $role) {
            if (!$user->hasRole($role)) {
                $user->assignRole($role);
                $newRoles[] = $role;
            }
        }
    
        // Check if there are new roles assigned before logging
        if (count($newRoles) > 0) {
            $roleList = implode(', ', $newRoles);
            ActivityLog::create([
                'user_id' => $authUser->id,
                'user_name' => $authUser->name,
                'activity' => "{$authUser->name} assigned the roles '{$roleList}' to {$user->name}",
            ]);
        }
    
        return response()->json(['success' => true, 'message' => 'Roles assigned successfully.'], 200);
    }
    
    

    // public function removeRoleFromUser(Request $request)
    // {
    //     $request->validate([
    //         'user_id' => 'required|exists:users,id',
    //         'role' => 'required|exists:roles,name',
    //     ]);
    
    //     $user = User::findOrFail($request->user_id);
    //     $user->removeRole($request->role);
    
    //     return response()->json(['success' => true, 'message' => 'Role removed successfully.'], 200);
    // }

    public function removeRoleFromUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|exists:roles,name',
        ]);
    
        $user = User::findOrFail($request->user_id);
        $authUser = $request->user(); // The authenticated user performing the role removal
    
        if ($user->hasRole($request->role)) {
            $user->removeRole($request->role);
    
            // Log the activity
            ActivityLog::create([
                'user_id' => $authUser->id,
                'user_name' => $authUser->name,
                'activity' => "{$authUser->name} removed the role '{$request->role}' from {$user->name}",
            ]);
    
            return response()->json(['success' => true, 'message' => 'Role removed successfully.'], 200);
        } else {
            return response()->json(['error' => 'User does not have the specified role'], 400);
        }
    }
    

}
