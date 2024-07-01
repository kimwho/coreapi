<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Parents;
use App\Models\ActivityLog;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Notifications\PasswordResetNotification;
use App\Models\ParentPasswordReset;
use App\Http\Resources\UserResource;
use App\Http\Requests\ParentForgotPasswordRequest; //req name
use App\Http\Requests\ParentResetPasswordRequest; //req name
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;

class ParentAuthController extends Controller
{

    public function register(Request $request)
    {
        
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
                'ParentFirstName.required' => 'first name is required.',
                'ParentLastName.required' => 'last name is required.',
                'ParentKhmerFirstName.required' => 'Khmer first name is required.',
                'ParentKhmerLastName.required' => 'Khmer last name is required.',
                'ParentDOB.required' => 'date of birth is required.',
                'ParentDOB.date' => 'date of birth must be a valid date.',
                'ParentContact.required' => 'contact is required.',
                'ParentContact.regex' => 'Contact should be at least 9 digits and only contain numbers.',
                'SexID.required' => 'Sex ID is required.',
                'SexID.numeric' => 'Sex ID must be a number.',
                'ParentIdentityNumber.required' => 'identity number is required.',
                'ParentIdentityNumber.numeric' => 'identity number must be a number.',
                'ParentImage.required' => 'image is required.',
                'ParentImage.image' => 'Profile picture must be an image file.',
                'ParentImage.mimes' => 'Profile picture must be a JPEG, PNG, JPG, or GIF file.',
                'ParentImage.max' => 'Profile picture must not exceed 10MB.',
                'ParentStreetNumber.required' => 'Street number is required.',
                'ParentStreetNumber.numeric' => 'Street number must be a number.',
                'ParentVillage.required' => 'Village is required.',
                'ParentSangkat.required' => 'Sangkat is required.',
                'ParentKhan.required' => 'Khan is required.',
                'ParentCity.required' => 'City is required.',
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
                'ParentFirstName' => 'required|string',
                'ParentLastName' => 'required|string',
                'ParentKhmerFirstName' => 'required|string',
                'ParentKhmerLastName' => 'required|string',
                'ParentDOB' => 'required|date',
                'ParentContact' => 'required|regex:/^\d{9,}$/',
                'SexID' => 'required|numeric',
                'ParentIdentityNumber' => 'required|numeric',
                'ParentImage' => 'required|image|mimes:jpeg,png,jpg,gif|max:10240',
                'ParentStreetNumber' => 'required|numeric',
                'ParentVillage' => 'required|string',
                'ParentSangkat' => 'required|string',
                'ParentKhan' => 'required|string',
                'ParentCity' => 'required|string',
                'DaycareID' => 'required|numeric',
            ], $messages);
    
            try {
                $validatedData = $validator->validate();
            } catch (ValidationException $e) {
                $errors = $e->validator->errors()->toArray();
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $errors,
                ], 422);
            }
            

        // Handle parent image upload
        $parentImagePath = $request->file('ParentImage')->store('parent', 'spaces');

        // Create the parent record
        $parent = new Parents;
        $parent->ParentFirstName = $request->ParentFirstName;
        $parent->ParentLastName = $request->ParentLastName;
        $parent->ParentKhmerFirstName = $request->ParentKhmerFirstName;
        $parent->ParentKhmerLastName = $request->ParentKhmerLastName;
        $parent->ParentDOB = $request->ParentDOB;
        $parent->ParentContact = $request->ParentContact;
        $parent->ParentIdentityNumber = $request->ParentIdentityNumber;
        $parent->ParentStreetNumber = $request->ParentStreetNumber;
        $parent->ParentVillage = $request->ParentVillage;
        $parent->ParentSangkat = $request->ParentSangkat;
        $parent->ParentKhan = $request->ParentKhan;
        $parent->ParentCity = $request->ParentCity;
        $parent->ParentImage = $parentImagePath;
        $parent->SexID = $request->SexID;
        $parent->DaycareID = $request->DaycareID;
        $parent->save();

        // Create the user record
        $user = new User;
        $user->name = $request->ParentFirstName . ' ' . $request->ParentLastName;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->ParentID = $parent->ParentID;
        $user->DaycareID = $parent->DaycareID;
        $user->save();

        // Assign default role to the user
        $defaultRole = Role::findById(7, 'web'); // Ensure this ID matches the intended role and specify the guard
        $user->assignRole($defaultRole->name);

        // Set the assigned role as the active role
        $user->active_role = $defaultRole->name;
        $user->save();

        // Log the activity
        $requestuser = $request->user();
        ActivityLog::create([
            'user_id' => $requestuser->id,
            'user_name' => $requestuser->name,
            'activity' => "{$requestuser->name} registered a new parent in Daycare",
        ]);

        // Create token for the parent
        $token = $parent->createToken('myapptoken')->plainTextToken;

        // Return success response if everything is successful
        return response()->json([
            'success' => true,
            'message' => 'Parent registered successfully!',
            'parent' => $parent,
            'user' => $user,
            'token' => $token
        ], 201);
    }

}
