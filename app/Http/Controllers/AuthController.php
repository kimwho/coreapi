<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Organization;
use App\Models\Daycare;
use App\Models\Parents;
use App\Models\Staff;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\Validator;
use App\Notifications\PasswordResetNotification;
use App\Models\PasswordReset;
use App\Http\Resources\UserResource;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
 
    public function login(Request $request)
    {
        try {
            // Validate the request data
            $validator = Validator::make($request->all(), [
                'email' => 'required|string|email',
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }

            $credentials = $request->only('email', 'password');

            // Check if the email exists in the users table
            $user = User::where('email', $credentials['email'])->first();
            if (!$user) {
                return response()->json(['message' => 'Email not found'], 404);
            }

            // Check the AccountStatusID
            if ($user->AccountStatusID != 1) {
                return response()->json(['message' => 'Your account is not active. Please contact the admin.'], 403);
            }

            // Attempt to authenticate the user
            if (!Auth::attempt($credentials)) {
                return response()->json(['message' => 'Invalid login credentials'], 401);
            }

            // Check if the user has roles assigned
            if ($user->roles->isEmpty()) {
                Auth::logout(); // Logout the user
                return response()->json(['message' => 'You do not have any assigned role. Please contact the admin for access.'], 403);
            }

            // Eager load related models and roles
            $user = Auth::user()->load('organization', 'daycare', 'staff', 'parent', 'admin', 'roles');

            // Generate a token for the authenticated user
            $token = $user->createToken('authToken')->plainTextToken;

            return response()->json(['token' => $token, 'user' => $user], 200);

        } catch (Exception $e) {
            // Handle any unexpected exceptions
            return response()->json(['message' => 'An error occurred during the login process', 'error' => $e->getMessage()], 500);
        }
    }

    public function forgot(ForgotPasswordRequest $request): JsonResponse{
        // Find the user by email
        $user = User::where('email', $request->email)->first();
    
        // If user email not found, return a 404 response
        if (!$user) {
            return response()->json(['message' => 'No email found'], 404);
        }
    
        // Generate a new reset password token
        $resetPasswordToken = str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
    
        // Create a new password reset token record
        PasswordReset::updateOrCreate(
            ['email' => $user->email],
            ['token' => $resetPasswordToken]
        );
    
        // Notify the user with the new password reset token
        $user->notify(new PasswordResetNotification($resetPasswordToken, $user->email));
    
        // Return a success response
        return response()->json(['message' => 'A code has been sent to your email!']);
    }
    
    public function reset(ResetPasswordRequest $request): JsonResponse{
        // Find the user by email
        $user = User::where('email', $request->email)->first();

        // If user not found, return a 404 response
        if (!$user) {
            return response()->json(['message' => 'No email found'], 404);
        }

        // Find the password reset request for the user
        $resetRequest = PasswordReset::where('email', $user->email)->first();

        // Check if no reset request found or token doesn't match, return a 400 response
        if (!$resetRequest || $resetRequest->token != $request->token) {
            return response()->json(['message' => 'Invalid or expired token'], 400);
        }

        // Update the user's password
        $user->update([
            'password' => bcrypt($request->password),
        ]);

        // Delete the password reset request
        $resetRequest->delete();

        // Create a new token for the user
        $token = $user->createToken('authToken')->plainTextToken;

        // Return a success response with a message and user data
        return response()->json([
            'message' => 'Password reset successfully!',
            'user' => $user,
            'token' => $token
        ], 201);
    }

}
