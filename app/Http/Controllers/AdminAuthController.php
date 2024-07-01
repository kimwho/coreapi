<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class AdminAuthController extends Controller
{

    public function register(Request $request){
        try {
            // Custom validation error messages
            $messages = [
                'email.required' => 'Email is required.',
                'email.email' => 'Please enter a valid email address.',
                'email.unique' => 'This email is already in use.',
                'password.required' => 'Password is required.',
                'password.min' => 'Password must be at least 8 characters.',
                'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.',
                'AdminName.required' => 'Admin name is required.',
                'AdminContact.required' => 'Admin contact is required.',
                'AdminContact.numeric' => 'Admin contact must be a number.',
                'SexID.required' => 'Sex ID is required.',
                'SexID.numeric' => 'Sex ID must be a number.',
                'AdminImage.required' => 'Admin image is required.',
                'AdminImage.image' => 'Admin image must be an image file.',
                'AdminImage.mimes' => 'Admin image must be a JPEG, PNG, JPG, or GIF file.',
                'AdminImage.max' => 'Admin image must not exceed 10MB.',
            ];
    
            // Validate the request with custom messages
            $validatedData = $request->validate([
                'email' => 'required|string|email|unique:users,email',
                'password' => [
                    'required',
                    'string',
                    'min:8', // Minimum length of 8 characters
                    'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[$@$!%*?&])[A-Za-z\d$@$!%*?&]+$/',
                    // At least one uppercase letter, one lowercase letter, one number, and one special character
                ],
                'AdminName' => 'required|string',
                'AdminContact' => 'required|numeric',
                'SexID' => 'required|numeric',
                'AdminImage' => 'required|image|mimes:jpeg,png,jpg,gif|max:10240',
            ], $messages);
    
        } catch (ValidationException $e) {
            // Validation failed, return error response with validation errors
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    
        // Handle admin image upload
        $adminImagePath = $request->file('AdminImage')->store('admin', 'spaces');
    
        // Create the admin
        $admin = new Admin;
        $admin->AdminName = $request->AdminName;
        $admin->AdminContact = $request->AdminContact;
        $admin->SexID = $request->SexID;
        $admin->AdminImage = $adminImagePath;
        $admin->save();
    
        // Create user
        $user = new User;
        $user->name = $request->AdminName;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->AdminID = $admin->AdminID;
        $user->save();
    
        // Assign default role to the user
        $defaultRole = Role::findById(1); // Assuming role id 1 corresponds to the default role for admin
        $user->assignRole($defaultRole);
    
        // Set the assigned role as the active role
        $user->active_role = $defaultRole->name;
        $user->save();
    
        // Generate token for the admin
        $token = $admin->createToken('myapptoken')->plainTextToken;
    
        // Return success response if everything is successful
        return response()->json([
            'success' => true,
            'message' => 'Admin registered successfully!',
            'admin' => $admin,
            'user' => $user,
            'token' => $token
        ], 201);
    }
    
    public function showspecificadmin($id){
        $admin = Admin::findOrFail($id);
        return response()->json($admin);
    }

    // public function updateadmin(Request $request, $DaycareID) {

    //     $validator = Validator::make($request->all(), [
    //         'DaycareName' => 'string',
    //         'DaycareKhmerName' => 'string',
    //         'DaycareContact' => 'string',
    //         'DaycareRepresentative' => 'string',
    //         'DaycareProofOfIdentity' => 'nullable|file|mimes:pdf,doc|max:10240',
    //         'DaycareImage' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
    //         'DaycareStreetNumber' => 'string',
    //         'DaycareVillage' => 'string',
    //         'DaycareSangkat' => 'string',
    //         'DaycareKhan' => 'string',
    //         'DaycareCity' => 'string',
    //         'OrganizationID' => 'numeric',
    //         'AccountStatusID' => 'numeric',
    //         'AdminID'=> 'numeric',
          
            
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['errors' => $validator->errors()], 400);
    //     }

    //     $daycare = Daycare::find($DaycareID);

    //     if (!$daycare) {
    //         return response()->json(['message' => 'Daycare not found'], 404);
    //     }

    //     if ($request->hasFile('DaycareProofOfIdentity') && $request->file('DaycareProofOfIdentity')->isValid() ) {

    //         $daycareProofPath = $request->file('DaycareProofOfIdentity')->store('daycare', 'spaces');

    //         if ($daycare->DaycareProofOfIdentity) {
    //             Storage::disk('spaces')->delete($daycare->DaycareProofOfIdentity);
    //         }
    //         $daycare->DaycareProofOfIdentity = $daycareProofPath;
    //     }

    //     if ($request->hasFile('DaycareImage') && $request->file('DaycareImage')->isValid() ) {

    //         $daycareImagePath = $request->file('DaycareImage')->store('daycare', 'spaces');

    //         if ($daycare->DaycareImage) {
    //             Storage::disk('spaces')->delete($daycare->DaycareImage);
    //         }
    //         $daycare->DaycareImage = $daycareImagePath;
    //     }

    //         $fieldsToUpdate = ['DaycareName', 'DaycareKhmerName', 'DaycareContact', 'DaycareRepresentative', 'DaycareStreetNumber', 'DaycareVillage', 'DaycareSangkat', 'DaycareKhan', 'DaycareCity', 'OrganizationID', 'AccountStatusID', 'AdminID'];
    //         foreach ($fieldsToUpdate as $field) {
    //             if ($request->has($field)) {
    //                 $daycare->$field = $request->$field;
    //             }
    //         }

    //     $daycare->save(); 


    //     // Update the user record if ParentFirstName or ParentLastName is changed
    //     $user = User::where('DaycareID', $DaycareID)->first();
    
    //     if ($user) {
    //         $nameUpdated = false;
    //         if ($request->has('DaycareName')) {
    //             $user->name = $request->DaycareName;
    //             $nameUpdated = true;
    //         }
            
    //         if ($nameUpdated) {
    //             $user->save();
    //         }
    //     }

    //     return response()->json([
    //         'message' => 'Daycare updated successfully',
    //     ], 200);
    // } 
}
