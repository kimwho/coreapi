<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Staff;
use App\Models\Daycare;
use App\Models\User;
use App\Models\Organization;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
class StaffController extends Controller
{
    public function allstaff(){
        $staff = Staff::with('daycare')->get()->map(function($staff) {
            $staffArr = $staff->toArray();
            
            if ($staff->daycare) {
                $staffArr['DaycareName'] = $staff->daycare->DaycareName;
            } else {
                $staffArr['DaycareName'] = null; // or any default value you prefer
            }
            
            unset($staffArr['daycare']); // Remove the nested daycare array
            return $staffArr;
        });
        
        return response()->json($staff);
    }
    
    public function allactivestaff(){
        $staff = Staff::where('AccountStatusID', 1)->get();
        return response()->json($staff);
    }

    public function allinactivestaff(){
        $staff = Staff::where('AccountStatusID', 2)->get();
        return response()->json($staff);
    }

    public function allStaffByDaycareID($daycareID){
        $staff = Staff::where('DaycareID', $daycareID)->get();
        $staffCount = $staff->count();
    
        if ($staff->isEmpty()) {
            return response()->json(['message' => 'No staffs found for the specified daycareID'], 404);
        }
    
        return response()->json([
            'staffs_count' => $staffCount,
            'staff' => $staff
            
        ]);
    }
    
    public function allActiveStaffsByDaycareID($daycareID){
        $activeStaffs = Staff::where('DaycareID', $daycareID)
                                ->where('AccountStatusID', 1)
                                ->get();
        $activeStaffsCount = $activeStaffs->count();
    
        if ($activeStaffs->isEmpty()) {
            return response()->json([
                'active_staffs' => [],
                'active_staffs_count' => 0,
                'message' => 'No active staffs found for the specified daycareID'
            ], 404);
        }
    
        return response()->json([
            'active_staffs' => $activeStaffs,
            'active_staffs_count' => $activeStaffsCount
        ]);
    }
    
    public function allInactiveStaffsByDaycareID($daycareID){
        $inactiveStaffs = Staff::where('DaycareID', $daycareID)
                                ->where('AccountStatusID', 2)
                                ->get();
        $inactiveStaffsCount = $inactiveStaffs->count();
    
        if ($inactiveStaffs->isEmpty()) {
            return response()->json([
                'inactive_staffs' => [],
                'inactive_staffs_count' => 0,
                'message' => 'No inactive staffs found for the specified daycareID'
            ], 404);
        }
    
        return response()->json([
            'inactive_staffs' => $inactiveStaffs,
            'inactive_staffs_count' => $inactiveStaffsCount
        ]);
    }
    
    public function showspecificstaff($id){
        $staff = Staff::findOrFail($id);
        return response()->json($staff);
    }
  
   
    public function updatestaff(Request $request, $StaffID) {
        $messages = [
            'StaffFirstName.string' => 'First name must be a string.',
            'StaffLastName.string' => 'Last name must be a string.',
            'StaffKhmerFirstName.string' => 'Khmer First Name must be a string.',
            'StaffKhmerLastName.string' => 'Khmer Last Name must be a string.',
            'StaffDOB.date' => 'Date of birth must be a valid date.',
            'StaffContact.regex' => 'Parent contact must be a string containing at least 9 numeric characters.',
            'StaffImage.image' => 'Profile Picture must be an image file.',
            'StaffImage.mimes' => 'Profile Picture must be a JPEG, PNG, JPG, or GIF file.',
            'StaffImage.max' => 'Profile picture must not exceed 10MB.',
            'SexID.numeric' => 'Sex ID must be a number.',
            'AccountStatusID.numeric' => 'Account status ID must be a number.',
            'DaycareID.numeric' => 'Daycare ID must be a number.',
            'StaffIdentityNumber.string' => 'Street number must be a string.',
            'StartedWorkDate.date' => 'Started Work Date must be a valid date.',
        ];
    
        $validator = Validator::make($request->all(), [
            'StaffFirstName' => 'string',
            'StaffLastName' => 'string',
            'StaffKhmerFirstName' => 'string',
            'StaffKhmerLastName' => 'string',
            'StaffDOB' => 'date',
            'StaffContact' => 'string|regex:/^[0-9]{9,}$/',
            'StaffImage' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
            'SexID' => 'numeric',
            'AccountStatusID' => 'numeric',
            'DaycareID' => 'numeric',
            'StaffIdentityNumber' => 'string',
            'StartedWorkDate' => 'date',
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
    
        $staff = Staff::find($StaffID);
    
        if (!$staff) {
            return response()->json(['message' => 'Staff not found'], 404);
        }
    
        // Capture existing values before updating
        $existingValues = [];
        foreach ($staff->getAttributes() as $key => $value) {
            $existingValues[$key] = $value;
        }
    
        // Handle StaffImage update
        $profilePictureUpdated = false;
        if ($request->hasFile('StaffImage') && $request->file('StaffImage')->isValid() ) {
            $staffImagePath = $request->file('StaffImage')->store('staff', 'spaces');
    
            if ($staff->StaffImage) {
                Storage::disk('spaces')->delete($staff->StaffImage);
            }
            $staff->StaffImage = $staffImagePath;
            $profilePictureUpdated = true;
        }
    
        // Update other fields
        $fieldsToUpdate = ['StaffFirstName', 'StaffLastName', 'StaffKhmerFirstName', 'StaffKhmerLastName', 'StaffDOB', 'StaffContact', 'SexID', 'AccountStatusID', 'DaycareID', 'StaffIdentityNumber', 'StartedWorkDate'];
        foreach ($fieldsToUpdate as $field) {
            if ($request->has($field)) {
                $staff->$field = $request->$field;
            }
        }
    
        // Save updated staff
        $staff->save();

        $user = User::where('StaffID', $StaffID)->first();
            
            if ($user) {
                $nameUpdated = false;
                if ($request->has('StaffFirstName')) {
                    $user->name = $request->StaffFirstName . ' ' . $staff->StaffLastName;
                    $nameUpdated = true;
                }
                if ($request->has('StaffLastName')) {
                    $user->name = $staff->StaffFirstName . ' ' . $request->StaffLastName;
                    $nameUpdated = true;
                }
                if ($nameUpdated) {
                    $user->save();
                }
    
                if ($request->has('AccountStatusID')) {
                    $user->AccountStatusID = $request->AccountStatusID;
                    $user->save();
                }
    
                if ($request->has('DaycareID')) {
                    $user->DaycareID = $request->DaycareID;
                    $user->save();
                }
            }

        // Log the activity
        $requestUser = $request->user();
        $staffName = "{$staff->StaffFirstName} {$staff->StaffLastName}";
    
        // Initialize formatted changes array
        $formattedChanges = [];
    
        // Define readable values for specific fields
        $statusLabels = [
            1 => 'active',
            2 => 'inactive'
        ];
    
        $sexLabels = [
            1 => 'Male',
            2 => 'Female',
            3 => 'Preferred not to say'
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
                    $staff->$field = $newValue;
        
                    // Log only if there's a change
                    if ($field === 'AccountStatusID') {
                        $oldStatus = $statusLabels[$oldValue] ?? 'unknown';
                        $newStatus = $statusLabels[$newValue] ?? 'unknown';
                        if ($oldStatus !== $newStatus) {
                            $activityMessage = "{$requestUser->name} changed status from '{$oldStatus}' to '{$newStatus}' for staff {$staffName}";
                            ActivityLog::create([
                                'user_id' => $requestUser->id,
                                'user_name' => $requestUser->name,
                                'activity' => $activityMessage,
                            ]);
                        }
                    } elseif ($field === 'SexID') {
                        $oldSex = $sexLabels[$oldValue] ?? 'unknown';
                        $newSex = $sexLabels[$newValue] ?? 'unknown';
                        if ($oldSex !== $newSex) {
                            $formattedChanges[] = "Sex: '{$oldSex}' to '{$newSex}'";
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
                $activityMessage = "{$requestUser->name} updated details and changed the profile picture of staff {$staffName}. Changes: {$changesString}";
            } else {
                $activityMessage = "{$requestUser->name} updated details of staff {$staffName}. Changes: {$changesString}";
            }
    
            ActivityLog::create([
                'user_id' => $requestUser->id,
                'user_name' => $requestUser->name,
                'activity' => $activityMessage,
            ]);
        } elseif ($profilePictureUpdated) {
            // If only profile picture updated without other changes
            $activityMessage = "{$requestUser->name} set a new profile picture for {$staffName}";
            ActivityLog::create([
                'user_id' => $requestUser->id,
                'user_name' => $requestUser->name,
                'activity' => $activityMessage,
            ]);
        }
    
        return response()->json([
            'message' => 'Staff updated successfully',
        ], 200);
    }
    
    public function activateStaffAccount(Request $request, $StaffID)
    {
        $staff = Staff::find($StaffID);
    
        if (!$staff) {
            return response()->json(['message' => 'Staff not found'], 404);
        }
    
        $staff->AccountStatusID = 1;
        $staff->save();
    
        // Update the corresponding User record
        $user = User::where('StaffID', $StaffID)->first();
        if ($user) {
            $user->AccountStatusID = 1;
            $user->save();
        }

        $requestUser = $request->user(); // Assuming you have authenticated user
        ActivityLog::create([
            'user_id' => $requestUser->id,
            'user_name' => $requestUser->name,
            'activity' => "{$requestUser->name} activated {$staff->StaffFirstName} {$staff->StaffLastName}'s account. ",
        ]);
        
        return response()->json(['message' => 'Staff account activated successfully'], 200);
    }
    
    public function deactivateStaffAccount(Request $request, $StaffID)
    {
        $staff = Staff::find($StaffID);
    
        if (!$staff) {
            return response()->json(['message' => 'Staff not found'], 404);
        }
    
        $staff->AccountStatusID = 2;
        $staff->save();
    
        // Update the corresponding User record
        $user = User::where('StaffID', $StaffID)->first();
        if ($user) {
            $user->AccountStatusID = 2;
            $user->save();
        }

        $requestUser = $request->user(); // Assuming you have authenticated user
        ActivityLog::create([
            'user_id' => $requestUser->id,
            'user_name' => $requestUser->name,
            'activity' => "{$requestUser->name} deactivated {$staff->StaffFirstName} {$staff->StaffLastName}'s account. ",
        ]);

        return response()->json(['message' => 'Staff account deactivated successfully'], 200);
    }
    
    //for organization
    // public function getByOrganizationId($organizationId)
    // {// Fetch the organization
    //     $organization = Organization::findOrFail($organizationId);

    //     // Get all daycares of the organization
    //     $daycares = $organization->daycares;

    //     // Initialize an empty array to store staff information
    //     $staffInformation = [];

    //     // Loop through each daycare and fetch its staff
    //     foreach ($daycares as $daycare) {
    //         // Get all staff of the daycare with daycare name
    //         $staff = $daycare->staff()->get();

    //         // Append daycare name to each staff member
    //         foreach ($staff as $staffMember) {
    //             $staffMemberData = $staffMember->toArray();
    //             $staffMemberData['DaycareName'] = $daycare->DaycareName;
    //             $staffInformation[] = $staffMemberData;
    //         }
    //     }

    //     // Return the staff information
    //     return response()->json($staffInformation);
    // }

    public function getAllStaffByOrganizationId($organizationId)
    {
        // Fetch the organization
        $organization = Organization::findOrFail($organizationId);

        // Get all daycares of the organization
        $daycares = $organization->daycares;

        // Initialize an empty array to store staff information
        $staffInformation = [];

        // Initialize a counter for the total number of staff members
        $totalStaffCount = 0;

        // Loop through each daycare and fetch its staff
        foreach ($daycares as $daycare) {
            // Get all staff of the daycare with daycare name
            $staff = $daycare->staff()->get();

            // Increment the total staff count
            $totalStaffCount += $staff->count();

            // Append daycare name to each staff member
            foreach ($staff as $staffMember) {
                $staffMemberData = $staffMember->toArray();
                $staffMemberData['DaycareName'] = $daycare->DaycareName;
                $staffInformation[] = $staffMemberData;
            }
        }

        // Add the total staff count to the response
        $response = [
            'totalStaffCount' => $totalStaffCount,
            'staffInformation' => $staffInformation
        ];

        // Return the staff information
        return response()->json($response);
    }

    public function getAllActiveStaffByOrganizationId($organizationId)
    {
        // Fetch the organization
        $organization = Organization::findOrFail($organizationId);

        // Get all daycares of the organization
        $daycares = $organization->daycares;

        // Initialize an empty array to store staff information
        $staffInformation = [];

        // Initialize a counter for the total number of staff members
        $totalStaffCount = 0;

        // Loop through each daycare and fetch its staff
        foreach ($daycares as $daycare) {
            // Get all staff of the daycare with daycare name and filter by AccountStatusID
            $staff = $daycare->staff()->where('AccountStatusID', 1)->get();

            // Increment the total staff count
            $totalStaffCount += $staff->count();

            // Append daycare name to each staff member
            foreach ($staff as $staffMember) {
                $staffMemberData = $staffMember->toArray();
                $staffMemberData['DaycareName'] = $daycare->DaycareName;
                $staffInformation[] = $staffMemberData;
            }
        }

        // Add the total staff count to the response
        $response = [
            'totalStaffCount' => $totalStaffCount,
            'activestaffInformation' => $staffInformation
        ];

        // Return the staff information
        return response()->json($response);
    }

    public function getAllInactiveStaffByOrganizationId($organizationId)
    {
        // Fetch the organization
        $organization = Organization::findOrFail($organizationId);

        // Get all daycares of the organization
        $daycares = $organization->daycares;

        // Initialize an empty array to store staff information
        $staffInformation = [];

        // Initialize a counter for the total number of staff members
        $totalStaffCount = 0;

        // Loop through each daycare and fetch its staff
        foreach ($daycares as $daycare) {
            // Get all staff of the daycare with daycare name and filter by AccountStatusID
            $staff = $daycare->staff()->where('AccountStatusID', 2)->get();

            // Increment the total staff count
            $totalStaffCount += $staff->count();

            // Append daycare name to each staff member
            foreach ($staff as $staffMember) {
                $staffMemberData = $staffMember->toArray();
                $staffMemberData['DaycareName'] = $daycare->DaycareName;
                $staffInformation[] = $staffMemberData;
            }
        }

        // Add the total staff count to the response
        $response = [
            'totalStaffCount' => $totalStaffCount,
            'inactivestaffInformation' => $staffInformation
        ];

        // Return the staff information
        return response()->json($response);
    }

}
