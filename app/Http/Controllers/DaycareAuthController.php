<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


use App\Models\Daycare;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\ActivityLog;
use App\Notifications\PasswordResetNotification;
use App\Models\DaycarePasswordReset;
use App\Http\Resources\UserResource;
use App\Http\Requests\DaycareForgotPasswordRequest; //req name
use App\Http\Requests\DaycareResetPasswordRequest; //req name
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;


class DaycareAuthController extends Controller
{

    public function register(Request $request){
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
            'DaycareName.required' => 'Daycare name is required.',
            'DaycareName.unique' => 'This daycare name is already in use.',
            'DaycareKhmerName.required' => 'Daycare Khmer name is required.',
            'DaycareKhmerName.unique' => 'This daycare Khmer name is already in use.',
            'DaycareContact.required' => 'Daycare contact is required.',
            'DaycareContact.regex' => 'Contact should be at least 9 digits and only contain numbers.',
            'DaycareRepresentative.required' => 'Daycare representative is required.',
            'DaycareProofOfIdentity.required' => 'Daycare proof of identity is required.',
            'DaycareProofOfIdentity.mimes' => 'Daycare proof of identity must be a PDF or DOC file.',
            'DaycareProofOfIdentity.max' => 'Daycare proof of identity must not exceed 10MB.',
            'DaycareImage.required' => 'Daycare image is required.',
            'DaycareImage.image' => 'Profile picture must be an image file.',
            'DaycareImage.mimes' => 'Profile picture must be a JPEG, PNG, JPG, or GIF file.',
            'DaycareImage.max' => 'Profile picture must not exceed 10MB.',
            'DaycareStreetNumber.required' => 'Street number is required.',
            'DaycareStreetNumber.numeric' => 'Street number must be a number.',
            'DaycareVillage.required' => 'Village is required.',
            'DaycareSangkat.required' => 'Sangkat is required.',
            'DaycareKhan.required' => 'Khan is required.',
            'DaycareCity.required' => 'City is required.',
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
            'DaycareName' => 'required|string|unique:Daycare,DaycareName',
            'DaycareKhmerName' => 'required|string|unique:Daycare,DaycareKhmerName',
            'DaycareContact' => 'required|regex:/^\d{9,}$/',
            'DaycareRepresentative' => 'required|string',
            'DaycareProofOfIdentity' => 'required|file|mimes:pdf,doc|max:10240',
            'DaycareImage' => 'required|image|mimes:jpeg,png,jpg,gif|max:10240',
            'DaycareStreetNumber' => 'required|numeric',
            'DaycareVillage' => 'required|string',
            'DaycareSangkat' => 'required|string',
            'DaycareKhan' => 'required|string',
            'DaycareCity' => 'required|string',
            'OrganizationID' => 'nullable|numeric',
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
    
        if ($request->hasFile('DaycareImage') && $request->file('DaycareImage')->isValid() &&
            $request->hasFile('DaycareProofOfIdentity') && $request->file('DaycareProofOfIdentity')->isValid()) {
    
            // Handle daycare image upload
            $dayImagePath = $request->file('DaycareImage')->store('daycare', 'spaces');
    
            // Handle daycare proof upload
            $dayProofPath = $request->file('DaycareProofOfIdentity')->store('daycare', 'spaces');
    
            // Create the daycare
            $daycare = new Daycare;
            $daycare->DaycareName = $request->DaycareName;
            $daycare->DaycareKhmerName = $request->DaycareKhmerName;
            $daycare->DaycareContact = $request->DaycareContact;
            $daycare->DaycareRepresentative = $request->DaycareRepresentative;
            $daycare->DaycareProofOfIdentity = $dayProofPath;
            $daycare->DaycareImage = $dayImagePath;
            $daycare->DaycareStreetNumber = $request->DaycareStreetNumber;
            $daycare->DaycareVillage = $request->DaycareVillage;
            $daycare->DaycareSangkat = $request->DaycareSangkat;
            $daycare->DaycareKhan = $request->DaycareKhan;
            $daycare->DaycareCity = $request->DaycareCity;
            $daycare->OrganizationID = $request->OrganizationID;
            $daycare->save();
    
            // Create user
            $user = new User;
            $user->name = $request->DaycareName;
            $user->email = $request->email;
            $user->password = bcrypt($request->password);
            $user->DaycareID = $daycare->DaycareID;
            $user->OrganizationID = $daycare->OrganizationID;
            $user->save();
    
            // Assign default role to the user
            $defaultRole = Role::findById(4, 'web'); // Ensure this ID matches the intended role
            $user->assignRole($defaultRole);
    
            // Set the assigned role as the active role
            $user->active_role = $defaultRole->name;
            $user->save();
    
            // Create token for the daycare
            $token = $daycare->createToken('myapptoken')->plainTextToken;
    
            // Log the activity if OrganizationID is provided
            if ($daycare->OrganizationID) {
                ActivityLog::create([
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'activity' => "A new daycare just registered under your organization.",
                ]);
            }

            // Return success response if everything is successful
            return response()->json([
                'success' => true,
                'message' => 'Daycare registered successfully!',
                'daycare' => $daycare,
                'user' => $user,
                'token' => $token
            ], 201);
        } else {
            // Handle case where file upload fails
            return response()->json([
                'success' => false,
                'message' => 'File upload failed. Please try again.',
            ], 400);
        }
    }

    public function registerdaycareadmin(Request $request){

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
            'DaycareID.required' => 'Daycare ID is required.',
            'DaycareID.numeric' => 'Daycare ID must be a number.',
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
            'DaycareID'=> 'required|numeric',
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
            $user->DaycareID = $request->DaycareID;
            $user->save();
    
            // Assign default role to the user
            $defaultRole = Role::findById(5, 'web'); // Ensure this ID matches the intended role and specify the guard
            $user->assignRole($defaultRole->name);
    
            // Set the assigned role as the active role
            $user->active_role = $defaultRole->name;
            $user->save();
    
            // Create token for the daycare
            $token = $user->createToken('myapptoken')->plainTextToken;
    
            // Log the activity
            $requestuser = $request->user();
            ActivityLog::create([
                'user_id' => $requestuser->id,
                'user_name' => $requestuser->name,
                'activity' => "{$requestuser->name} registered a new daycare admin in their daycare",
            ]);

            // Get Daycare 
            $daycare = Daycare::find($request->DaycareID);
    
            // Return success response if everything is successful
            return response()->json([
                'token' => $token,
                'success' => true,
                'message' => 'New Daycare Admin registered successfully!',
                'user' => $user,
                'daycare' => $daycare
            ], 201);
    
    } 
    
    
}
