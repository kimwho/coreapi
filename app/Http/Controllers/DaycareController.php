<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Daycare;
use App\Models\User;
use App\Models\Organization;
use App\Models\Staff;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Log; 
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
class DaycareController extends Controller
{
    public function allDaycares(){
        // Fetch daycares along with the 'organizationName' attribute
        $daycares = Daycare::with('organization:OrganizationID,OrganizationName')->get();
    
        // Transform each daycare to include OrganizationName directly
        $daycares->transform(function ($daycare) {
            $daycareData = $daycare->toArray();
            if ($daycare->organization) {
                $daycareData['OrganizationName'] = $daycare->organization->OrganizationName;
                unset($daycareData['organization']);
            } else {
                $daycareData['OrganizationName'] = null;
            }
            return $daycareData;
        });
    
        $daycaresCount = $daycares->count();
    
        return response()->json([
            'daycares' => $daycares,
            'daycares_count' => $daycaresCount
        ]);
    }
    
    public function getDaycareByOrganizationId($organizationId){
        // Find the organization
        $organization = Organization::find($organizationId);
    
        if (!$organization) {
            return response()->json(['error' => 'Organization not found'], 404);
        }
    
        // Retrieve all daycares associated with the organization
        $daycares = $organization->daycares;
    
        // Fetch the organization name
        $organizationName = $organization->OrganizationName;
    
        // Iterate over each daycare and add the organization name
        foreach ($daycares as $daycare) {
            $daycare['OrganizationName'] = $organizationName;
        }
    
        return response()->json(['daycares' => $daycares], 200);
    }
    
    public function allActiveDaycares(){
        $activeDaycares = Daycare::where('AccountStatusID', 1)->get();
        $activeDaycaresCount = $activeDaycares->count();
    
        return response()->json([
            'active_daycares' => $activeDaycares,
            'active_daycares_count' => $activeDaycaresCount
        ]);
    }
    
    public function allInactiveDaycares(){
        $inactiveDaycares = Daycare::where('AccountStatusID', 2)->get();
        $inactiveDaycaresCount = $inactiveDaycares->count();
    
        return response()->json([
            'inactive_daycares' => $inactiveDaycares,
            'inactive_daycares_count' => $inactiveDaycaresCount
        ]);
    }
    
    public function allDaycaresByOrganizationID($OrganizationID){
        $daycares = Daycare::where('OrganizationID', $OrganizationID)->get();
        $daycaresCount = $daycares->count();
    
        if ($daycares->isEmpty()) {
            return response()->json(['message' => 'No daycares found for the specified organizationID'], 404);
        }
    
        return response()->json([
            'daycares' => $daycares,
            'daycares_count' => $daycaresCount
        ]);
    }
    
    public function allActiveDaycaresByOrganizationID($OrganizationID)
    {
        $activeDaycares = Daycare::where('OrganizationID', $OrganizationID)
                                ->where('AccountStatusID', 1)
                                ->get();
        $activeDaycaresCount = $activeDaycares->count();

        if ($activeDaycares->isEmpty()) {
            return response()->json([
                'message' => 'No active daycares found for the specified organizationID',
                'active_daycares_count' => 0
            ], 404);
        }

        return response()->json([
            'active_daycares' => $activeDaycares,
            'active_daycares_count' => $activeDaycaresCount
        ]);
    }

    public function allInactiveDaycaresByOrganizationID($OrganizationID)
    {
        $inactiveDaycares = Daycare::where('OrganizationID', $OrganizationID)
                                ->where('AccountStatusID', 2)
                                ->get();
        $inactiveDaycaresCount = $inactiveDaycares->count();

        if ($inactiveDaycares->isEmpty()) {
            return response()->json([
                'message' => 'No active daycares found for the specified organizationID',
                'inactive_daycares_count' => 0
            ], 404);
        }

        return response()->json([
            'inactive_daycares' => $inactiveDaycares,
            'inactive_daycares_count' => $inactiveDaycaresCount
        ]);
    }

    public function showspecificdaycare($id){
        $daycare = Daycare::findOrFail($id);
        return response()->json($daycare);
    }

    public function getDaycareByDaycareAndOrganizationId($daycareId, $organizationId){
        $daycare = Daycare::where('DaycareID', $daycareId)
                          ->where('OrganizationID', $organizationId)
                          ->first();
    
        if (!$daycare) {
            return response()->json(['message' => 'Daycare not found'], 404);
        }
    
        return response()->json($daycare);
    }
    
    // public function updatedaycare(Request $request, $DaycareID)
    // {
    //     // Custom validation error messages
    //     $messages = [
    //         'DaycareName.string' => 'Daycare name must be a string.',
    //         'DaycareKhmerName.string' => 'Daycare Khmer name must be a string.',
    //         'DaycareContact.regex' => 'Contact should be at least 9 digits and only contain numbers.',
    //         'DaycareRepresentative.string' => 'Daycare representative must be a string.',
    //         'DaycareProofOfIdentity.mimes' => 'Daycare proof of identity must be a PDF or DOC file.',
    //         'DaycareProofOfIdentity.max' => 'Daycare proof of identity must not exceed 10MB.',
    //         'DaycareImage.image' => 'Daycare image must be an image file.',
    //         'DaycareImage.mimes' => 'Daycare image must be a JPEG, PNG, JPG, or GIF file.',
    //         'DaycareImage.max' => 'Daycare image must not exceed 10MB.',
    //         'DaycareStreetNumber.string' => 'Street number must be a string.',
    //         'DaycareVillage.string' => 'Village must be a string.',
    //         'DaycareSangkat.string' => 'Sangkat must be a string.',
    //         'DaycareKhan.string' => 'Khan must be a string.',
    //         'DaycareCity.string' => 'City must be a string.',
    //         'OrganizationID.numeric' => 'Organization ID must be a number.',
    //         'AccountStatusID.numeric' => 'Account status ID must be a number.',
    //     ];

    //     $validator = Validator::make($request->all(), [
    //         'DaycareName' => 'string',
    //         'DaycareKhmerName' => 'string',
    //         'DaycareContact' => 'string|regex:/^\d{9,}$/',
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
    //     ], $messages);

    //     try {
    //         $validatedData = $validator->validate();
    //     } catch (ValidationException $e) {
    //         // Return error response with validation errors
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Validation failed',
    //             'errors' => $e->errors(),
    //         ], 422);
    //     }

    //     $daycare = Daycare::find($DaycareID);

    //     if (!$daycare) {
    //         return response()->json(['message' => 'Daycare not found'], 404);
    //     }

    //     $changes = [];
    //     $profilePictureUpdated = false;

    //     if ($request->hasFile('DaycareProofOfIdentity') && $request->file('DaycareProofOfIdentity')->isValid()) {
    //         $daycareProofPath = $request->file('DaycareProofOfIdentity')->store('daycare', 'spaces');
    //         if ($daycare->DaycareProofOfIdentity) {
    //             Storage::disk('spaces')->delete($daycare->DaycareProofOfIdentity);
    //         }
    //         $daycare->DaycareProofOfIdentity = $daycareProofPath;
    //     }

    //     if ($request->hasFile('DaycareImage') && $request->file('DaycareImage')->isValid()) {
    //         $daycareImagePath = $request->file('DaycareImage')->store('daycare', 'spaces');
    //         if ($daycare->DaycareImage) {
    //             Storage::disk('spaces')->delete($daycare->DaycareImage);
    //         }
    //         $daycare->DaycareImage = $daycareImagePath;
    //         $profilePictureUpdated = true;
    //     }

    //     $fieldsToUpdate = ['DaycareName', 'DaycareKhmerName', 'DaycareContact', 'DaycareRepresentative', 'DaycareStreetNumber', 'DaycareVillage', 'DaycareSangkat', 'DaycareKhan', 'DaycareCity', 'OrganizationID', 'AccountStatusID'];
    //     foreach ($fieldsToUpdate as $field) {
    //         if ($request->has($field)) {
    //             $changes[$field] = ['old' => $daycare->$field, 'new' => $request->$field];
    //             $daycare->$field = $request->$field;
    //         }
    //     }

    //     $daycare->save();

    //     // Update the user record if DaycareName is changed
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

    //         if ($request->has('AccountStatusID')) {
    //             $user->AccountStatusID = $request->AccountStatusID;
    //             $user->save();
    //         }
    //     }

    //     // Log the activity
    //     $requestUser = $request->user();
    //     $daycareName = $daycare->DaycareName;

    //     if ($profilePictureUpdated && !empty($changes)) {
    //         // Format changes into a readable string
    //         $formattedChanges = [];
    //         foreach ($changes as $attribute => $change) {
    //             $formattedChanges[] = "{$attribute}: '{$change['old']}' to '{$change['new']}'";
    //         }
    //         $changesString = implode(', ', $formattedChanges);

    //         $activityMessage = "{$requestUser->name} updated details and changed the profile picture of {$daycareName}. Changes: {$changesString}";
    //     } elseif ($profilePictureUpdated) {
    //         $activityMessage = "{$requestUser->name} changed the profile picture of {$daycareName}";
    //     } elseif (!empty($changes)) {
    //         // Format changes into a readable string
    //         $formattedChanges = [];
    //         foreach ($changes as $attribute => $change) {
    //             $formattedChanges[] = "{$attribute}: '{$change['old']}' to '{$change['new']}'";
    //         }
    //         $changesString = implode(', ', $formattedChanges);

    //         $activityMessage = "{$requestUser->name} updated details of {$daycareName}. Changes: {$changesString}";
    //     } else {
    //         $activityMessage = "{$requestUser->name} updated details of {$daycareName}";
    //     }

    //     ActivityLog::create([
    //         'user_id' => $requestUser->id,
    //         'user_name' => $requestUser->name,
    //         'activity' => $activityMessage,
    //     ]);

    //     return response()->json([
    //         'message' => 'Daycare updated successfully',
    //     ], 200);
    // }

    public function updatedaycare(Request $request, $DaycareID)
    {
        // Custom validation error messages
        $messages = [
            'DaycareName.string' => 'Daycare name must be a string.',
            'DaycareKhmerName.string' => 'Daycare Khmer name must be a string.',
            'DaycareContact.regex' => 'Contact should be at least 9 digits and only contain numbers.',
            'DaycareRepresentative.string' => 'Daycare representative must be a string.',
            'DaycareProofOfIdentity.mimes' => 'Daycare proof of identity must be a PDF or DOC file.',
            'DaycareProofOfIdentity.max' => 'Daycare proof of identity must not exceed 10MB.',
            'DaycareImage.image' => 'Daycare image must be an image file.',
            'DaycareImage.mimes' => 'Daycare image must be a JPEG, PNG, JPG, or GIF file.',
            'DaycareImage.max' => 'Daycare image must not exceed 10MB.',
            'DaycareStreetNumber.string' => 'Street number must be a string.',
            'DaycareVillage.string' => 'Village must be a string.',
            'DaycareSangkat.string' => 'Sangkat must be a string.',
            'DaycareKhan.string' => 'Khan must be a string.',
            'DaycareCity.string' => 'City must be a string.',
            'OrganizationID.numeric' => 'Organization ID must be a number.',
            'AccountStatusID.numeric' => 'Account status ID must be a number.',
        ];

        $validator = Validator::make($request->all(), [
            'DaycareName' => 'string',
            'DaycareKhmerName' => 'string',
            'DaycareContact' => 'string|regex:/^\d{9,}$/',
            'DaycareRepresentative' => 'string',
            'DaycareProofOfIdentity' => 'nullable|file|mimes:pdf,doc|max:10240',
            'DaycareImage' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
            'DaycareStreetNumber' => 'string',
            'DaycareVillage' => 'string',
            'DaycareSangkat' => 'string',
            'DaycareKhan' => 'string',
            'DaycareCity' => 'string',
            'OrganizationID' => 'numeric',
            'AccountStatusID' => 'numeric',
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

        $daycare = Daycare::find($DaycareID);

        if (!$daycare) {
            return response()->json(['message' => 'Daycare not found'], 404);
        }

        // Capture existing values before updating
        $existingValues = [];
        foreach ($daycare->getAttributes() as $key => $value) {
            $existingValues[$key] = $value;
        }
    
        // Handle DaycareImage update
        $profilePictureUpdated = false;

        if ($request->hasFile('DaycareProofOfIdentity') && $request->file('DaycareProofOfIdentity')->isValid()) {
            $daycareProofPath = $request->file('DaycareProofOfIdentity')->store('daycare', 'spaces');
            if ($daycare->DaycareProofOfIdentity) {
                Storage::disk('spaces')->delete($daycare->DaycareProofOfIdentity);
            }
            $daycare->DaycareProofOfIdentity = $daycareProofPath;
        }

        if ($request->hasFile('DaycareImage') && $request->file('DaycareImage')->isValid()) {
            $daycareImagePath = $request->file('DaycareImage')->store('daycare', 'spaces');
            if ($daycare->DaycareImage) {
                Storage::disk('spaces')->delete($daycare->DaycareImage);
            }
            $daycare->DaycareImage = $daycareImagePath;
            $profilePictureUpdated = true;
        }

        $fieldsToUpdate = ['DaycareName', 'DaycareKhmerName', 'DaycareContact', 'DaycareRepresentative', 'DaycareStreetNumber', 'DaycareVillage', 'DaycareSangkat', 'DaycareKhan', 'DaycareCity', 'OrganizationID', 'AccountStatusID'];
        foreach ($fieldsToUpdate as $field) {
            if ($request->has($field)) {
                $daycare->$field = $request->$field;
            }
        }

        $daycare->save();

        // Update the user record if DaycareName is changed
        $user = User::where('DaycareID', $DaycareID)->first();
        if ($user) {
            $nameUpdated = false;
            if ($request->has('DaycareName')) {
                $user->name = $request->DaycareName;
                $nameUpdated = true;
            }
            if ($nameUpdated) {
                $user->save();
            }
        }

        $users = User::where('DaycareID', $DaycareID)->get();
        if ($request->has('AccountStatusID')) {
            foreach ($users as $user) {
                $user->AccountStatusID = $request->AccountStatusID;
                $user->save();
            }
        }

         // Log the activity
         $requestUser = $request->user();
         $daycareName = "{$daycare->DaycareName}";
     
         // Initialize formatted changes array
         $formattedChanges = [];
     
         // Define readable values for specific fields
         $statusLabels = [
             1 => 'active',
             2 => 'inactive'
         ];
     
        
         foreach ($fieldsToUpdate as $field) {
             if ($request->has($field) && $request->input($field) != null) {
                 $oldValue = $existingValues[$field];
                 $newValue = $request->$field;
         
                 // Convert both values to strings to ensure consistent comparison
                 if (is_numeric($oldValue) || is_numeric($newValue)) {
                     $oldValue = strval($oldValue);
                     $newValue = strval($newValue);
                 }
         
                 if ($oldValue !== $newValue) {
                     $daycare->$field = $newValue;
         
                     // Log only if there's a change
                     if ($field === 'AccountStatusID') {
                         $oldStatus = $statusLabels[$oldValue] ?? 'unknown';
                         $newStatus = $statusLabels[$newValue] ?? 'unknown';
                         if ($oldStatus !== $newStatus) {
                             $activityMessage = "{$requestUser->name} changed status from '{$oldStatus}' to '{$newStatus}' for daycare {$daycareName}";
                             ActivityLog::create([
                                 'user_id' => $requestUser->id,
                                 'user_name' => $requestUser->name,
                                 'activity' => $activityMessage,
                             ]);
                         }
                     } else {
                         $formattedChanges[] = "{$field}: '{$oldValue}' to '{$newValue}'";
                     }
                 }
             }
         }
         
     
         // Create general activity log for other changes if any
         if (!empty($formattedChanges)) {
             $changesString = implode(', ', $formattedChanges);
     
             if ($profilePictureUpdated) {
                 $activityMessage = "{$requestUser->name} updated details and changed the profile picture of daycare {$daycareName}. Changes: {$changesString}";
             } else {
                 $activityMessage = "{$requestUser->name} updated details of daycare {$daycareName}. Changes: {$changesString}";
             }
     
             ActivityLog::create([
                 'user_id' => $requestUser->id,
                 'user_name' => $requestUser->name,
                 'activity' => $activityMessage,
             ]);
         } elseif ($profilePictureUpdated) {
             // If only profile picture updated without other changes
             $activityMessage = "{$requestUser->name} set a new profile picture for daycare {$daycareName}";
             ActivityLog::create([
                 'user_id' => $requestUser->id,
                 'user_name' => $requestUser->name,
                 'activity' => $activityMessage,
             ]);
         }       

        return response()->json([
            'message' => 'Daycare updated successfully',
        ], 200);
    }

    public function activateDaycareAccount(Request $request, $DaycareID)
    {
        $daycare = Daycare::find($DaycareID);
    
        if (!$daycare) {
            return response()->json(['message' => 'Daycare not found'], 404);
        }
      
        // Activate the account (set AccountStatusID to 1)
        $daycare->AccountStatusID = 1;
        $daycare->save();
    
        // Update the corresponding User records
        $users = User::where('DaycareID', $DaycareID)->get();
        foreach ($users as $user) {
            $user->AccountStatusID = 1;
            $user->save();
    
        }
        // Log the overall activity for daycare activation
        $requestUser = $request->user(); // Assuming you have authenticated user
        ActivityLog::create([
            'user_id' => $requestUser->id,
            'user_name' => $requestUser->name,
            'activity' => "{$requestUser->name} activated {$daycare->DaycareName}'s account. ",
        ]);
    
        return response()->json(['message' => 'Daycare account activated successfully'], 200);
    }
    
    public function deactivateDaycareAccount(Request $request, $DaycareID)
    {
        $daycare = Daycare::find($DaycareID);

        if (!$daycare) {
            Log::warning("Attempted to deactivate non-existent Daycare with ID: $DaycareID");
            return response()->json(['message' => 'Daycare not found'], 404);
        }

        $daycare->AccountStatusID = 2;
        $daycare->save();

        $users = User::where('DaycareID', $DaycareID)->get();
        foreach ($users as $user) {
            try {
                $user->AccountStatusID = 2;
                $user->save();
            } catch (\Exception $e) {
                Log::error("Failed to update AccountStatusID for user {$user->id}: " . $e->getMessage());
            }
        }

        $requestUser = $request->user();
        ActivityLog::create([
            'user_id' => $requestUser->id,
            'user_name' => $requestUser->name,
            'activity' => "{$requestUser->name} deactivated {$daycare->DaycareName}'s account.",
        ]);

        Log::info("Deactivated Daycare account for ID: $DaycareID");
        return response()->json(['message' => 'Daycare account deactivated successfully'], 200);
    }

    public function getAdminsByDaycareId($daycareId)
    {
        try {
            // Fetch users based on OrganizationID and role
            $users = User::where('DaycareID', $daycareId)
                         ->role('daycare admin') // Filter by role
                         ->get();

            if ($users->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No daycare admins found for the specified Daycare ID',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $users,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching users.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getUsersByDaycareID($daycareId)
    {
        // Find the daycare
        $daycare = Daycare::find($daycareId);

        if (!$daycare) {
            return response()->json(['error' => 'Daycare not found'], 404);
        }

        // Eager load users with their roles
        $daycare->load('users.roles');

        // Initialize array to store user information
        $userInformation = [];

        // Get all users of the daycare
        foreach ($daycare->users as $user) {
            $roles = $user->roles->pluck('name')->toArray();

            // If user has no roles, set $roles to null
            $roles = empty($roles) ? null : $roles;

            

            $userInformation[] = [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'email' => $user->email,
                'daycare_name' => $daycare->DaycareName,
                'daycare_id' => $daycare->DaycareID,
                'accountstatus' => $user->AccountStatusID,
                'roles' => $roles,
            ];
        }

        // Sort the user information by role names
        usort($userInformation, function ($a, $b) {
            $roleOrder = [
                'daycare superadmin' => 0,
                'daycare admin' => 1,
                'daycare staff' => 2,
                'daycare parent' => 3
            ];

            $rolesA = $a['roles'] ?? []; // Handle null roles
            $rolesB = $b['roles'] ?? []; // Handle null roles

            // Find the minimum role index for each user
            $minIndexA = PHP_INT_MAX;
            $minIndexB = PHP_INT_MAX;

            foreach ($rolesA as $role) {
                if (isset($roleOrder[$role])) {
                    $minIndexA = min($minIndexA, $roleOrder[$role]);
                }
            }

            foreach ($rolesB as $role) {
                if (isset($roleOrder[$role])) {
                    $minIndexB = min($minIndexB, $roleOrder[$role]);
                }
            }

            // Compare the minimum role index
            if ($minIndexA === $minIndexB) {
                return 0;
            }

            return ($minIndexA < $minIndexB) ? -1 : 1;
        });

        // Return the combined information
        return response()->json($userInformation);
    }

}
