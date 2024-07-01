<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Parents;
use App\Models\User;
use App\Models\Daycare;
use App\Models\Organization;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
class ParentController extends Controller
{

    public function allparent()
    {
        $parents = Parents::with('daycare:DaycareID,DaycareName')->get()->map(function($parent) {
            $parentArr = $parent->toArray();
            $parentArr['DaycareName'] = $parent->daycare ? $parent->daycare->DaycareName : null;
            unset($parentArr['daycare']); // Remove the nested daycare array
            return $parentArr;
        });
        
        return response()->json($parents);
    }
    
    public function allactiveparent(){
        $parents = Parents::where('AccountStatusID', 1)->get();
        return response()->json($parents);
    }

    public function allinactiveparent(){
        $parents = Parents::where('AccountStatusID', 2)->get();
        return response()->json($parents);
    }

    public function allParentsByDaycareID($daycareID){
        $parents = Parents::where('DaycareID', $daycareID)->get();
        $parentsCount = $parents->count();
    
        if ($parents->isEmpty()) {
            return response()->json(['message' => 'No parents found for the specified daycareID'], 404);
        }
    
        return response()->json([
            'parents' => $parents,
            'parents_count' => $parentsCount
        ]);
    }
    
    public function allActiveParentsByDaycareID($daycareID){
        $activeParents = Parents::where('DaycareID', $daycareID)
                                ->where('AccountStatusID', 1)
                                ->get();
        $activeParentsCount = $activeParents->count();
    
        if ($activeParents->isEmpty()) {
            return response()->json([
                'active_parents' => [],
                'active_parents_count' => 0,
                'message' => 'No active parents found for the specified daycareID'
            ], 404);
        }
    
        return response()->json([
            'active_parents' => $activeParents,
            'active_parents_count' => $activeParentsCount
        ]);
    }
    
    public function allInactiveParentsByDaycareID($daycareID){
        $inactiveParents = Parents::where('DaycareID', $daycareID)
                                  ->where('AccountStatusID', 2)
                                  ->get();
        $inactiveParentsCount = $inactiveParents->count();
    
        if ($inactiveParents->isEmpty()) {
            return response()->json([
                'inactive_parents' => [],
                'inactive_parents_count' => 0,
                'message' => 'No inactive parents found for the specified daycareID'
            ], 404);
        }
    
        return response()->json([
            'inactive_parents' => $inactiveParents,
            'inactive_parents_count' => $inactiveParentsCount
        ]);
    }
    
    public function showspecificparent($id){
        $parent = Parents::findOrFail($id);
        return response()->json($parent);
    }

    public function getParentWithChildren($parentId){
        $parent = Parents::with('children')->find($parentId);
    
        return $parent;
    }

    public function listParentsWithChildren(){
        $parentsWithChildren = Parents::with('children')->get();

        return $parentsWithChildren;
    }

    // public function updateparent(Request $request, $ParentID) {
    //     // Define custom error messages
    //     $messages = [
    //         'ParentFirstName.string' => 'First name must be a string.',
    //         'ParentLastName.string' => ' Last name must be a string.',
    //         'ParentKhmerFirstName.string' => 'Khmer First Name must be a string.',
    //         'ParentKhmerLastName.string' => 'Khmer Last Name must be a string.',
    //         'ParentDOB.date' => 'Date of birth nust be a valid date.',
    //         'ParentContact.regex' => 'Parent contact must be a string containing at least 9 numeric characters.',
    //         'ParentImage.image' => 'Profile Picture must be an image file.',
    //         'ParentImage.mimes' => 'Profile Picture must be a JPEG, PNG, JPG, or GIF file.',
    //         'ParentImage.max' => 'Profile picture must not exceed 10MB.',
    //         'SexID.numeric' => 'Sex ID must be a number.',
    //         'AccountStatusID.numeric' => 'Account status ID must be a number.',
    //         'DaycareID.numeric' => 'Daycare ID must be a number.',
    //         'ParentIdentityNumber.string' => 'Street number must be a string.',
    //         'ParentStreetNumber.string' => 'Street number must be a string.',
    //         'ParentVillage.string' => 'Village must be a string.',
    //         'ParentSangkat.string' => 'Sangkat must be a string.',
    //         'ParentKhan.string' => 'Khan must be a string.',
    //         'ParentCity.string' => 'City must be a string.',
    //     ];

    //     // Validate the request
    //     $validator = Validator::make($request->all(), [
    //         'ParentFirstName' => 'string',
    //         'ParentLastName' => 'string',
    //         'ParentKhmerFirstName' => 'string',
    //         'ParentKhmerLastName' => 'string',
    //         'ParentDOB' => 'date',
    //         'ParentContact' => 'string|regex:/^[0-9]{9,}$/',
    //         'ParentImage' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
    //         'SexID' => 'numeric',
    //         'AccountStatusID' => 'numeric',
    //         'DaycareID' => 'numeric',
    //         'ParentIdentityNumber' => 'string',
    //         'ParentStreetNumber' => 'string',
    //         'ParentVillage' => 'string',
    //         'ParentSangkat' => 'string',
    //         'ParentKhan' => 'string',
    //         'ParentCity' => 'string',
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

    //     $parent = Parents::find($ParentID);

    //     if (!$parent) {
    //         return response()->json(['message' => 'Parent not found'], 404);
    //     }

    //     $changes = [];
    //     $profilePictureUpdated = false;

    //     if ($request->hasFile('ParentImage') && $request->file('ParentImage')->isValid()) {
    //         // Handle Parent image upload
    //         $parentImagePath = $request->file('ParentImage')->store('parent', 'spaces');
    //         // Delete old image if exists
    //         if ($parent->ParentImage) {
    //             Storage::disk('spaces')->delete($parent->ParentImage);
    //         }
    //         $parent->ParentImage = $parentImagePath;
    //         $profilePictureUpdated = true;
    //     }

    //     // Update parent fields if they are present in the request
    //     $fieldsToUpdate = ['ParentFirstName', 'ParentLastName', 'ParentKhmerFirstName', 'ParentKhmerLastName', 'ParentDOB', 'ParentContact', 'SexID', 'AccountStatusID', 'DaycareID', 'ParentIdentityNumber', 'ParentStreetNumber', 'ParentVillage', 'ParentSangkat', 'ParentKhan', 'ParentCity'];
    //     foreach ($fieldsToUpdate as $field) {
    //         if ($request->has($field)) {
    //             $changes[$field] = ['old' => $parent->$field, 'new' => $request->$field];
    //             $parent->$field = $request->$field;
    //         }
    //     }

    //     $parent->save();

    //     // Update the user record if ParentFirstName or ParentLastName is changed
    //     $user = User::where('ParentID', $ParentID)->first();

    //     if ($user) {
    //         $nameUpdated = false;
    //         if ($request->has('ParentFirstName')) {
    //             $user->name = $request->ParentFirstName . ' ' . $parent->ParentLastName;
    //             $nameUpdated = true;
    //         }
    //         if ($request->has('ParentLastName')) {
    //             $user->name = $parent->ParentFirstName . ' ' . $request->ParentLastName;
    //             $nameUpdated = true;
    //         }

    //         if ($nameUpdated) {
    //             $user->save();
    //         }

    //         if ($request->has('AccountStatusID')) {
    //             $user->AccountStatusID = $request->AccountStatusID;
    //             $user->save();
    //         }

    //         if ($request->has('DaycareID')) {
    //             $user->DaycareID = $request->DaycareID;
    //             $user->save();
    //         }
    //     }

    //          // Log the activity
    //          $requestUser = $request->user();
    //          $parentName = "{$parent->ParentFirstName} {$parent->ParentLastName}";
     
    //          // Filter the changes to include only modified fields
    //          $filteredChanges = array_filter($changes, function($change) {
    //              return (string)$change['old'] !== (string)$change['new'];
    //          });
     
    //          // Define readable values for specific fields
    //          $statusLabels = [
    //              1 => 'activated',
    //              2 => 'deactivated'
    //          ];
     
    //          $sexLabels = [
    //              1 => 'Male',
    //              2 => 'Female',
    //              3 => 'Preferred not to say'
    //          ];
     
    //          // Initialize formatted changes
    //          $formattedChanges = [];
    //          $activityMessageCreated = false;
     
    //          foreach ($filteredChanges as $attribute => $change) {
    //              if ($attribute == 'AccountStatusID' && isset($statusLabels[$change['new']])) {
    //                  // Special handling for AccountStatusID
    //                  $activityMessage = "{$requestUser->name} {$statusLabels[$change['new']]} {$parentName}'s account";
    //                  // Create the activity log
    //                  ActivityLog::create([
    //                      'user_id' => $requestUser->id,
    //                      'user_name' => $requestUser->name,
    //                      'activity' => $activityMessage,
    //                  ]);
    //                  $activityMessageCreated = true; // Mark that a special log was created
    //              } elseif ($attribute == 'SexID' && isset($sexLabels[$change['new']])) {
    //                  // Special handling for SexID
    //                  $formattedChanges[] = "Sex: '{$sexLabels[$change['old']]}' to '{$sexLabels[$change['new']]}'";
    //              } else {
    //                  // General handling for other attributes
    //                  $formattedChanges[] = "{$attribute}: '{$change['old']}' to '{$change['new']}'";
    //              }
    //          }
     
    //          // Combine changes into a string
    //          $changesString = implode(', ', $formattedChanges);
     
    //          if ($profilePictureUpdated && !empty($formattedChanges)) {
    //              $activityMessage = "{$requestUser->name} updated details and changed the profile picture of {$parentName}. Changes: {$changesString}";
    //              $activityMessageCreated = true; // Mark that a log was created
    //          } elseif ($profilePictureUpdated) {
    //              $activityMessage = "{$requestUser->name} changed the profile picture of {$parentName}";
    //              $activityMessageCreated = true; // Mark that a log was created
    //          } elseif (!empty($formattedChanges)) {
    //              $activityMessage = "{$requestUser->name} updated details of {$parentName}. Changes: {$changesString}";
    //              $activityMessageCreated = true; // Mark that a log was created
    //          }
     
    //          if ($activityMessageCreated) {
    //              // Create the activity log for general updates and profile picture changes
    //              ActivityLog::create([
    //                  'user_id' => $requestUser->id,
    //                  'user_name' => $requestUser->name,
    //                  'activity' => $activityMessage,
    //              ]);
    //          }

    //     ActivityLog::create([
    //         'user_id' => $requestUser->id,
    //         'user_name' => $requestUser->name,
    //         'activity' => $activityMessage,
    //     ]);

    //     return response()->json([
    //         'message' => 'Parent updated successfully',
    //     ], 200);
    // }

    public function updateparent(Request $request, $ParentID) {
        // Define custom error messages
        $messages = [
            'ParentFirstName.string' => 'First name must be a string.',
            'ParentLastName.string' => ' Last name must be a string.',
            'ParentKhmerFirstName.string' => 'Khmer First Name must be a string.',
            'ParentKhmerLastName.string' => 'Khmer Last Name must be a string.',
            'ParentDOB.date' => 'Date of birth nust be a valid date.',
            'ParentContact.regex' => 'Parent contact must be a string containing at least 9 numeric characters.',
            'ParentImage.image' => 'Profile Picture must be an image file.',
            'ParentImage.mimes' => 'Profile Picture must be a JPEG, PNG, JPG, or GIF file.',
            'ParentImage.max' => 'Profile picture must not exceed 10MB.',
            'SexID.numeric' => 'Sex ID must be a number.',
            'AccountStatusID.numeric' => 'Account status ID must be a number.',
            'DaycareID.numeric' => 'Daycare ID must be a number.',
            'ParentIdentityNumber.string' => 'Street number must be a string.',
            'ParentStreetNumber.string' => 'Street number must be a string.',
            'ParentVillage.string' => 'Village must be a string.',
            'ParentSangkat.string' => 'Sangkat must be a string.',
            'ParentKhan.string' => 'Khan must be a string.',
            'ParentCity.string' => 'City must be a string.',
        ];

        // Validate the request
        $validator = Validator::make($request->all(), [
            'ParentFirstName' => 'string',
            'ParentLastName' => 'string',
            'ParentKhmerFirstName' => 'string',
            'ParentKhmerLastName' => 'string',
            'ParentDOB' => 'date',
            'ParentContact' => 'string|regex:/^[0-9]{9,}$/',
            'ParentImage' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
            'SexID' => 'numeric',
            'AccountStatusID' => 'numeric',
            'DaycareID' => 'numeric',
            'ParentIdentityNumber' => 'string',
            'ParentStreetNumber' => 'string',
            'ParentVillage' => 'string',
            'ParentSangkat' => 'string',
            'ParentKhan' => 'string',
            'ParentCity' => 'string',
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

        $parent = Parents::find($ParentID);

        if (!$parent) {
            return response()->json(['message' => 'Parent not found'], 404);
        }

        //changes
        // Capture existing values before updating
        $existingValues = [];
        foreach ($parent->getAttributes() as $key => $value) {
            $existingValues[$key] = $value;
        }

        // Handle ParentImage update
        $profilePictureUpdated = false;
        if ($request->hasFile('ParentImage') && $request->file('ParentImage')->isValid()) {
            // Handle Parent image upload
            $parentImagePath = $request->file('ParentImage')->store('parent', 'spaces');
            // Delete old image if exists
            if ($parent->ParentImage) {
                Storage::disk('spaces')->delete($parent->ParentImage);
            }
            $parent->ParentImage = $parentImagePath;
            $profilePictureUpdated = true;
        }

        // Update parent fields if they are present in the request
        $fieldsToUpdate = ['ParentFirstName', 'ParentLastName', 'ParentKhmerFirstName', 'ParentKhmerLastName', 'ParentDOB', 'ParentContact', 'SexID', 'AccountStatusID', 'DaycareID', 'ParentIdentityNumber', 'ParentStreetNumber', 'ParentVillage', 'ParentSangkat', 'ParentKhan', 'ParentCity'];
        foreach ($fieldsToUpdate as $field) {
            if ($request->has($field)) {
                $parent->$field = $request->$field;
            }
        }

        $parent->save();

        // Update the user record if ParentFirstName or ParentLastName is changed
        $user = User::where('ParentID', $ParentID)->first();

        if ($user) {
            $nameUpdated = false;
            if ($request->has('ParentFirstName')) {
                $user->name = $request->ParentFirstName . ' ' . $parent->ParentLastName;
                $nameUpdated = true;
            }
            if ($request->has('ParentLastName')) {
                $user->name = $parent->ParentFirstName . ' ' . $request->ParentLastName;
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
        $parentName = "{$parent->ParentFirstName} {$parent->ParentLastName}";
    
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
                    $parent->$field = $newValue;
        
                    // Log only if there's a change
                    if ($field === 'AccountStatusID') {
                        $oldStatus = $statusLabels[$oldValue] ?? 'unknown';
                        $newStatus = $statusLabels[$newValue] ?? 'unknown';
                        if ($oldStatus !== $newStatus) {
                            $activityMessage = "{$requestUser->name} changed status from '{$oldStatus}' to '{$newStatus}' for parent {$parentName}";
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
                $activityMessage = "{$requestUser->name} updated details and changed the profile picture of {$parentName}. Changes: {$changesString}";
            } else {
                $activityMessage = "{$requestUser->name} updated details of {$parentName}. Changes: {$changesString}";
            }
    
            ActivityLog::create([
                'user_id' => $requestUser->id,
                'user_name' => $requestUser->name,
                'activity' => $activityMessage,
            ]);
        } elseif ($profilePictureUpdated) {
            // If only profile picture updated without other changes
            $activityMessage = "{$requestUser->name} set a new profile picture for {$parentName}";
            ActivityLog::create([
                'user_id' => $requestUser->id,
                'user_name' => $requestUser->name,
                'activity' => $activityMessage,
            ]);
        }     

        return response()->json([
            'message' => 'Parent updated successfully',
        ], 200);
    }
        
    public function activateParentAccount(Request $request, $parentId)
    {
        // Find the parent
        $parent = Parents::find($parentId);
    
        if (!$parent) {
            return response()->json(['message' => 'Parent not found'], 404);
        }
    
        // Activate the account (set AccountStatusID to 1)
        $parent->AccountStatusID = 1;
        $parent->save();
    
        // Update the corresponding User record
        $user = User::where('ParentID', $parentId)->first();
        if ($user) {
            $user->AccountStatusID = 1;
            $user->save();
        }
    
        $requestUser = $request->user(); // Assuming you have authenticated user
        ActivityLog::create([
            'user_id' => $requestUser->id,
            'user_name' => $requestUser->name,
            'activity' => "{$requestUser->name} activated {$parent->ParentFirstName} {$parent->ParentLastName}'s account. ",
        ]);

        return response()->json(['message' => 'Parent account activated successfully'], 200);
    }
    
    public function deactivateParentAccount(Request $request, $parentId)
    {
        // Find the parent
        $parent = Parents::find($parentId);
    
        if (!$parent) {
            return response()->json(['message' => 'Parent not found'], 404);
        }
    
        // Deactivate the account (set AccountStatusID to 2)
        $parent->AccountStatusID = 2;
        $parent->save();
    
        // Update the corresponding User record
        $user = User::where('ParentID', $parentId)->first();
        if ($user) {
            $user->AccountStatusID = 2;
            $user->save();
        }

        $requestUser = $request->user(); // Assuming you have authenticated user
        ActivityLog::create([
            'user_id' => $requestUser->id,
            'user_name' => $requestUser->name,
            'activity' => "{$requestUser->name} deactivated {$parent->ParentFirstName} {$parent->ParentLastName}'s account. ",
        ]);
    
        return response()->json(['message' => 'Parent account deactivated successfully'], 200);
    }
    
    public function deleteparent($id){
        $parent = Parents::findOrFail($id);
        $parent->delete();
        
        return response()->json(['message' => 'Parent deleted successfully'], 204);
    }

    // public function showparentbyorgid($organizationId)
    // {
    //     // Fetch the organization
    //     $organization = Organization::findOrFail($organizationId);

    //     // Get all daycares of the organization
    //     $daycares = $organization->daycares;

    //     // Initialize an empty array to store parent information
    //     $parentInformation = [];

    //     // Loop through each daycare and fetch its parents
    //     foreach ($daycares as $daycare) {
    //         // Get all parents of the daycare with daycare name
    //         $parents = $daycare->parents()->get();

    //         // Append daycare name to each parent
    //         foreach ($parents as $parent) {
    //             $parentData = $parent->toArray();
    //             $parentData['DaycareName'] = $daycare->DaycareName;
    //             $parentInformation[] = $parentData;
    //         }
    //     }

    //     // Return the parent information
    //     return response()->json($parentInformation);
    // }

    public function showParentByOrgId($organizationId)
    {
        // Fetch the organization
        $organization = Organization::findOrFail($organizationId);

        // Get all daycares of the organization
        $daycares = $organization->daycares;

        // Initialize an empty array to store parent information
        $parentInformation = [];

        // Initialize a counter for the total number of parents
        $totalParentCount = 0;

        // Loop through each daycare and fetch its parents
        foreach ($daycares as $daycare) {
            // Get all parents of the daycare with daycare name
            $parents = $daycare->parents()->get();

            // Increment the total parent count
            $totalParentCount += $parents->count();

            // Append daycare name to each parent
            foreach ($parents as $parent) {
                $parentData = $parent->toArray();
                $parentData['DaycareName'] = $daycare->DaycareName;
                $parentInformation[] = $parentData;
            }
        }

        // Add the total parent count to the response
        $response = [
            'totalParentCount' => $totalParentCount,
            'parentInformation' => $parentInformation
        ];

        // Return the parent information
        return response()->json($response);
    }

    public function showAllActiveParentByOrgId($organizationId)
    {
        // Fetch the organization
        $organization = Organization::findOrFail($organizationId);

        // Get all daycares of the organization
        $daycares = $organization->daycares;

        // Initialize an empty array to store parent information
        $parentInformation = [];

        // Initialize a counter for the total number of parents
        $totalParentCount = 0;

        // Loop through each daycare and fetch its parents
        foreach ($daycares as $daycare) {
            // Get all parents of the daycare with daycare name
            $parents = $daycare->parents()->where('AccountStatusID', 1)->get();

            // Increment the total parent count
            $totalParentCount += $parents->count();

            // Append daycare name to each parent
            foreach ($parents as $parent) {
                $parentData = $parent->toArray();
                $parentData['DaycareName'] = $daycare->DaycareName;
                $parentInformation[] = $parentData;
            }
        }

        // Add the total parent count to the response
        $response = [
            'totalParentCount' => $totalParentCount,
            'activeparentInformation' => $parentInformation
        ];

        // Return the parent information
        return response()->json($response);
    }

    public function showAllInactiveParentByOrgId($organizationId)
    {
        // Fetch the organization
        $organization = Organization::findOrFail($organizationId);

        // Get all daycares of the organization
        $daycares = $organization->daycares;

        // Initialize an empty array to store parent information
        $parentInformation = [];

        // Initialize a counter for the total number of parents
        $totalParentCount = 0;

        // Loop through each daycare and fetch its parents
        foreach ($daycares as $daycare) {
            // Get all parents of the daycare with daycare name
            $parents = $daycare->parents()->where('AccountStatusID', 2)->get();

            // Increment the total parent count
            $totalParentCount += $parents->count();

            // Append daycare name to each parent
            foreach ($parents as $parent) {
                $parentData = $parent->toArray();
                $parentData['DaycareName'] = $daycare->DaycareName;
                $parentInformation[] = $parentData;
            }
        }

        // Add the total parent count to the response
        $response = [
            'totalParentCount' => $totalParentCount,
            'inactiveparentInformation' => $parentInformation
        ];

        // Return the parent information
        return response()->json($response);
    }
}
