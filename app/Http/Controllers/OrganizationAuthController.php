<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Notifications\PasswordResetNotification;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

use App\Models\OrgPasswordReset;
use App\Http\Resources\UserResource;
use App\Http\Requests\OrgForgotPasswordRequest;
use App\Http\Requests\OrgResetPasswordRequest;

use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
class OrganizationAuthController extends Controller 
{
    
    public function register(Request $request){
        
            // Custom validation error messages
        $messages = [
                'OrganizationName.required' => 'Organization name is required.',
                'OrganizationName.unique' => 'This organization name is already in use.',
                'OrganizationKhmerName.required' => 'Organization Khmer name is required.',
                'OrganizationKhmerName.unique' => 'This organization Khmer name is already in use.',
                'email.required' => 'Email is required.',
                'email.email' => 'Please enter a valid email address.',
                'email.unique' => 'This email is already in use.',
                'password.required' => 'Password is required.',
                'password.min' => 'Password must be at least 8 characters.',
                'password.uppercase' => 'Password must contain at least one uppercase letter.',
                'password.lowercase' => 'Password must contain at least one lowercase letter.',
                'password.number' => 'Password must contain at least one number.',
                'password.special' => 'Password must contain at least one special character.',
                'OrganizationContact.required' => 'Organization contact is required.',
                'OrganizationContact.regex' => 'Contact should be at least 9 digits and only contain numbers.',
                'OrganizationRepresentative.required' => 'Organization representative is required.',
                'OrganizationProofOfIdentity.required' => 'Organization proof of identity is required.',
                'OrganizationProofOfIdentity.mimes' => 'Organization proof of identity must be a PDF or DOC file.',
                'OrganizationProofOfIdentity.max' => 'Organization proof of identity must not exceed 10MB.',
                'OrganizationImage.required' => 'Organization image is required.',
                'OrganizationImage.image' => 'Organization image must be an image file.',
                'OrganizationImage.mimes' => 'Organization image must be a JPEG, PNG, JPG, or GIF file.',
                'OrganizationImage.max' => 'Organization image must not exceed 10MB.',
                'OrganizationStreetNumber.required' => 'Street number is required.',
                'OrganizationStreetNumber.numeric' => 'Street number must be a number.',
                'OrganizationVillage.required' => 'Village is required.',
                'OrganizationSangkat.required' => 'Sangkat is required.',
                'OrganizationKhan.required' => 'Khan is required.',
                'OrganizationCity.required' => 'City is required.',
            ]; 

            // Validate the request with custom messages
            
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|unique:users,email',
            'password' => [
                'required',
                'string',
                'min:8',
                function($attribute, $value, $fail) {
                    if (!preg_match('/[A-Z]/', $value)) {
                        $fail('Password must contain at least one uppercase letter.');
                    }
                    if (!preg_match('/[a-z]/', $value)) {
                        $fail('Password must contain at least one lowercase letter.');
                    }
                    if (!preg_match('/\d/', $value)) {
                        $fail('Password must contain at least one number.');
                    }
                    if (!preg_match('/[$@$!%*?&]/', $value)) {
                        $fail('Password must contain at least one special character.');
                    }
                }
            ],
            'OrganizationName' => 'required|string|unique:Organization,OrganizationName',
            'OrganizationKhmerName' => 'required|string|unique:Organization,OrganizationKhmerName',
            'OrganizationContact' => 'required|regex:/^\d{9,}$/',
            'OrganizationRepresentative' => 'required|string',
            'OrganizationProofOfIdentity' => 'required|file|mimes:pdf,doc|max:10240',
            'OrganizationImage' => 'required|image|mimes:jpeg,png,jpg,gif|max:10240',
            'OrganizationStreetNumber' => 'required|numeric',
            'OrganizationVillage' => 'required|string',
            'OrganizationSangkat' => 'required|string',
            'OrganizationKhan' => 'required|string',
            'OrganizationCity' => 'required|string',
        ], $messages);

        try {
            $validatedData = $validator->validate();
        } catch (ValidationException $e) {
            // Return error response with validation errors
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }

        if ($request->hasFile('OrganizationImage') && $request->file('OrganizationImage')->isValid() && $request->hasFile('OrganizationProofOfIdentity') && $request->file('OrganizationProofOfIdentity')->isValid()) {
            // Handle org image upload
            $OrgImagePath = $request->file('OrganizationImage')->store('organization', 'spaces');

            // Handle org proof upload
            $OrgProofPath = $request->file('OrganizationProofOfIdentity')->store('organization', 'spaces');

            // Create the organization
            $org = new Organization;
            $org->OrganizationName = $request->OrganizationName;
            $org->OrganizationKhmerName = $request->OrganizationKhmerName;
            $org->OrganizationContact = $request->OrganizationContact;
            $org->OrganizationRepresentative = $request->OrganizationRepresentative;
            $org->OrganizationProofOfIdentity = $OrgProofPath;
            $org->OrganizationImage = $OrgImagePath;
            $org->OrganizationStreetNumber = $request->OrganizationStreetNumber;
            $org->OrganizationVillage = $request->OrganizationVillage;
            $org->OrganizationSangkat = $request->OrganizationSangkat;
            $org->OrganizationKhan = $request->OrganizationKhan;
            $org->OrganizationCity = $request->OrganizationCity;
            $org->save();

            // Create user
            $user = new User;
            $user->name = $request->OrganizationName;
            $user->email = $request->email;
            $user->password = bcrypt($request->password);
            $user->OrganizationID = $org->OrganizationID;
            $user->save();

              


            // Assign default role to the user
            $defaultRole = Role::findById(2, 'web'); // Ensure this ID matches the intended role
            $user->assignRole($defaultRole);

            // Set the assigned role as the active role
            $user->active_role = $defaultRole->name;
            $user->save();

            // Create token for the organization
            $token = $org->createToken('myapptoken')->plainTextToken;

            // Return success response if everything is successful
            return response()->json([
                'success' => true,
                'message' => 'Organization registered successfully!',
                'org' => $org,
                'user' => $user,
                'token' => $token
            ], 201);
        } 
        else {
            // Handle case where file upload fails
            return response()->json([
                'success' => false,
                'message' => 'File upload failed. Please try again.',
            ], 400);
        }
    
    }

    public function registerorganizationadmin(Request $request){
        // Custom validation error messages
        $messages = [
            'email.required' => 'Email is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email is already in use.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.uppercase' => 'Password must contain at least one uppercase letter.',
            'password.lowercase' => 'Password must contain at least one lowercase letter.',
            'password.number' => 'Password must contain at least one number.',
            'password.special' => 'Password must contain at least one special character.',
            'name.required' => 'Name is required.',
            'name.unique' => 'This name is already taken.',
            'OrganizationID.required' => 'Organization ID is required.',
            'OrganizationID.numeric' => 'Organization ID must be a number.',
        ];
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|unique:users,email',
            'password' => [
                'required',
                'string',
                'min:8',
                function($attribute, $value, $fail) {
                    if (!preg_match('/[A-Z]/', $value)) {
                        $fail('Password must contain at least one uppercase letter.');
                    }
                    if (!preg_match('/[a-z]/', $value)) {
                        $fail('Password must contain at least one lowercase letter.');
                    }
                    if (!preg_match('/\d/', $value)) {
                        $fail('Password must contain at least one number.');
                    }
                    if (!preg_match('/[$@$!%*?&]/', $value)) {
                        $fail('Password must contain at least one special character.');
                    }
                }
            ],
            'name' => 'required|string|unique:users,name',
            'OrganizationID'=> 'required|numeric',
        ], $messages);

        try {
            $validatedData = $validator->validate();
        } catch (ValidationException $e) {
            // Return error response with validation errors
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }


    
            // Create user
            $user = new User;
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = bcrypt($request->password);
            $user->OrganizationID = $request->OrganizationID;
            $user->save();
    
            // Assign default role to the user
            $defaultRole = Role::findById(3, 'web'); // Ensure this ID matches the intended role and specify the guard
            $user->assignRole($defaultRole->name);
    
            // Set the assigned role as the active role
            $user->active_role = $defaultRole->name;
            $user->save();
    
            // Create token for the user
            $token = $user->createToken('myapptoken')->plainTextToken;
    
            // Get organization 
            $organization = Organization::find($request->OrganizationID);
    
            // Return success response if everything is successful
            return response()->json([
                'token' => $token,
                'success' => true,
                'message' => 'New Organization Admin registered successfully!',
                'user' => $user,
                'organization' => $organization
            ], 201);
    
        
    }

    // public function registerorganizationadmin(Request $request){


    //     try {
    //         // Custom validation error messages
    //         $messages = [
    //             'email.required' => 'Email is required.',
    //             'email.email' => 'Please enter a valid email address.',
    //             'email.unique' => 'This email is already in use.',
    //             'password.required' => 'Password is required.',
    //             'password.min' => 'Password must be at least 8 characters.',
    //             'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.',
    //             'name.required' => 'Name is required.',
    //             'name.unique' => 'This name is already taken.',
    //             'OrganizationID.required' => 'Organization ID is required.',
    //             'OrganizationID.numeric' => 'Organization ID must be a number.',
    //         ];
    
    //         // Validate the request with custom messages
    //         $validatedData = $request->validate([
    //             'email' => 'required|string|email|unique:users,email',
    //             'password' => [
    //                 'required',
    //                 'string',
    //                 'min:8', // Minimum length of 8 characters
    //                 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[$@$!%*?&])[A-Za-z\d$@$!%*?&]+$/',
    //                 // At least one uppercase letter, one lowercase letter, one number, and one special character
    //             ],
    //             'name' => 'required|string|unique:users,name',
    //             'OrganizationID'=> 'required|numeric',
    //         ], $messages);
    
    //         // Create user
    //         $user = new User;
    //         $user->name = $request->name;
    //         $user->email = $request->email;
    //         $user->password = bcrypt($request->password);
    //         $user->OrganizationID = $request->OrganizationID;
    //         $user->save();
    
    //         // Assign default role to the user
    //         $defaultRole = Role::findById(3, 'web'); // Ensure this ID matches the intended role and specify the guard
    //         $user->assignRole($defaultRole->name);
    
    //         // Set the assigned role as the active role
    //         $user->active_role = $defaultRole->name;
    //         $user->save();
    
    //         // Create token for the user
    //         $token = $user->createToken('myapptoken')->plainTextToken;
    
    //         // Get organization 
    //         $organization = Organization::find($request->OrganizationID);
    
    //         // Return success response if everything is successful
    //         return response()->json([
    //             'token' => $token,
    //             'success' => true,
    //             'message' => 'New Organization Admin registered successfully!',
    //             'user' => $user,
    //             'organization' => $organization
    //         ], 201);
    
    //     } catch (ValidationException $e) {
    //         // Return error response with validation errors
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Validation failed',
    //             'errors' => $e->errors(),
    //         ], 422);
    //     } catch (\Exception $e) {
    //         // Return error response for any other exceptions
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'An error occurred while processing your request.',
    //             'errors' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
    
}
