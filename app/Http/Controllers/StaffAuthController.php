<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Notifications\PasswordResetNotification;
use App\Models\User;
use App\Models\Staff;
use App\Models\Daycare;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\StaffPasswordReset;
use App\Http\Resources\UserResource;
use App\Http\Requests\StaffForgotPasswordRequest; //req name
use App\Http\Requests\StaffResetPasswordRequest; //req name
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
class StaffAuthController extends Controller
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
                'StaffFirstName.required' => 'First name is required.',
                'StaffLastName.required' => 'Last name is required.',
                'StaffKhmerFirstName.required' => 'Khmer first name is required.',
                'StaffKhmerLastName.required' => 'Khmer last name is required.',
                'StaffDOB.required' => 'Date of birth is required.',
                'StaffDOB.date' => 'Date of birth must be a valid date.',
                'StaffContact.required' => 'Contact is required.',
                'StaffContact.regex' => 'Contact should be at least 9 digits and only contain numbers.',
                'StaffIdentityNumber.required' => 'Identity number is required.',
                'StaffIdentityNumber.numeric' => 'Identity number must be a number.',
                'StartedWorkDate.required' => 'Started work date is required.',
                'StartedWorkDate.date' => 'Started work date must be a valid date.',
                'StaffImage.required' => 'Staff image is required.',
                'StaffImage.image' => 'Profile picture must be an image file.',
                'StaffImage.mimes' => 'Profile picture must be a JPEG, PNG, JPG, or GIF file.',
                'StaffImage.max' => 'Profile picture must not exceed 10MB.',
                'SexID.required' => 'Sex is required.',
                'SexID.numeric' => 'Sex ID must be a number.',
                'DaycareID.required' => 'Daycare is required.',
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
                'StaffFirstName' => 'required|string',
                'StaffLastName' => 'required|string',
                'StaffKhmerFirstName' => 'required|string',
                'StaffKhmerLastName' => 'required|string',
                'StaffDOB' => 'required|date',
                'StaffContact' => 'required|regex:/^\d{9,}$/',
                'StaffIdentityNumber' => 'required|numeric',
                'StartedWorkDate' => 'required|date',
                'StaffImage' => 'required|image|mimes:jpeg,png,jpg,gif|max:10240',
                'SexID' => 'required|numeric',
                'DaycareID' => 'required|numeric',
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

            
        

        // Handle staff image upload
        $staffImagePath = $request->file('StaffImage')->store('staff', 'spaces');

        // Create the staff
        $staff = new Staff;
        $staff->StaffFirstName = $request->StaffFirstName;
        $staff->StaffLastName = $request->StaffLastName;
        $staff->StaffKhmerFirstName = $request->StaffKhmerFirstName;
        $staff->StaffKhmerLastName = $request->StaffKhmerLastName;
        $staff->StaffDOB = $request->StaffDOB;
        $staff->StaffContact = $request->StaffContact;
        $staff->StaffIdentityNumber = $request->StaffIdentityNumber;
        $staff->StartedWorkDate = $request->StartedWorkDate;
        $staff->StaffImage = $staffImagePath;
        $staff->SexID = $request->SexID;
        $staff->DaycareID = $request->DaycareID;
        $staff->save();

        // Create user
        $user = new User;
        $user->name = $request->StaffFirstName . ' ' . $request->StaffLastName;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->StaffID = $staff->StaffID;
        $user->DaycareID = $staff->DaycareID;
        $user->save();

       // Assign default role to the user
       $defaultRole = Role::findById(6, 'web'); // Ensure this ID matches the intended role and specify the guard
       $user->assignRole($defaultRole->name);

        // Set the assigned role as the active role
        $user->active_role = $defaultRole->name;
        $user->save();

         // Log the activity
         $requestuser = $request->user();
         ActivityLog::create([
             'user_id' => $requestuser->id,
             'user_name' => $requestuser->name,
             'activity' => "{$requestuser->name} registered a new staff in Daycare",
         ]);

        // Generate token for the staff
        $token = $staff->createToken('myapptoken')->plainTextToken;

        // Return success response if everything is successful
        return response()->json([
            'success' => true,
            'message' => 'Staff registered successfully!',
            'staff' => $staff,
            'user' => $user,
            'token' => $token
        ], 201);
    }

}
