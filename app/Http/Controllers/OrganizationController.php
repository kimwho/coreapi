<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Organization;
use App\Models\Daycare;
use App\Models\Parents;
use App\Models\Staff;
use App\Models\Child;
use App\Models\User;
use App\Models\Milestone;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;

class OrganizationController extends Controller
{
    public function allorganizations(){
        $organizations = Organization::all();
        $organizationCount = Organization::count();
    
        return response()->json([
            'count' => $organizationCount,
            'organizations' => $organizations
            
        ]);
    }

    public function allactiveorganizations(){
        $organization = Organization::where('AccountStatusID', 1)->get();
        return response()->json($organization);
    }

    public function allinactiveorganizations(){
        $organization = Organization::where('AccountStatusID', 2)->get();
        return response()->json($organization);
    }

    public function showspecificorganization($id){
        $organization = Organization::findOrFail($id);
        return response()->json($organization);
    }
       
    // public function updateorganization(Request $request, $OrganizationID) {
    //     $messages = [
    //         'OrganizationName.string' => 'Organization name must be a string.',
    //         'OrganizationKhmerName.string' => 'Organization Khmer name must be a string.',
    //         'OrganizationContact.regex' => 'Organization contact must be a string containing at least 9 numeric characters.',
    //         'OrganizationRepresentative.string' => 'Organization representative must be a string.',
    //         'OrganizationProofOfIdentity.mimes' => 'Organization proof of identity must be a PDF or DOC file.',
    //         'OrganizationProofOfIdentity.max' => 'Organization proof of identity must not exceed 10MB.',
    //         'OrganizationImage.image' => 'Organization image must be an image file.',
    //         'OrganizationImage.mimes' => 'Profile Picture must be a JPEG, PNG, JPG, or GIF file.',
    //         'OrganizationImage.max' => 'Profile picture must not exceed 10MB.',
    //         'OrganizationStreetNumber.string' => 'Street number must be a string.',
    //         'OrganizationVillage.string' => 'Village must be a string.',
    //         'OrganizationSangkat.string' => 'Sangkat must be a string.',
    //         'OrganizationKhan.string' => 'Khan must be a string.',
    //         'OrganizationCity.string' => 'City must be a string.',
    //         'OrganizationID.numeric' => 'Organization ID must be a number.',
    //         'AccountStatusID.numeric' => 'Account status ID must be a number.',

    //         // Add more custom error messages for other fields if needed
    //     ];
    //     $validator = Validator::make($request->all(), [
    //         'OrganizationName' => 'string',
    //         'OrganizationKhmerName' => 'string',
    //         'OrganizationContact' => 'string|regex:/^[0-9]{9,}$/',
    //         'OrganizationRepresentative' => 'string',
    //         'OrganizationProofOfIdentity' => 'nullable|file|mimes:pdf,doc|max:10240',
    //         'OrganizationImage' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
    //         'OrganizationStreetNumber' => 'numeric',
    //         'OrganizationVillage' => 'string',
    //         'OrganizationSangkat' => 'string',
    //         'OrganizationKhan' => 'string',
    //         'OrganizationCity' => 'string',
    //         'AccountStatusID'=> 'numeric',

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

    //     $organization = Organization::find($OrganizationID);

    //     if (!$organization) {
    //         return response()->json(['message' => 'Organization not found'], 404);
    //     }

    //     $changes = [];
    //     $profilePictureUpdated = false;

    //     if ($request->hasFile('OrganizationProofOfIdentity') && $request->file('OrganizationProofOfIdentity')->isValid() ) {

    //         $orgProofPath = $request->file('OrganizationProofOfIdentity')->store('organization', 'spaces');

    //         if ($organization->OrganizationProofOfIdentity) {
    //             Storage::disk('spaces')->delete($organization->OrganizationProofOfIdentity);
    //         }
    //         $organization->OrganizationProofOfIdentity = $orgProofPath;
    //     }

    //     if ($request->hasFile('OrganizationImage') && $request->file('OrganizationImage')->isValid() ) {

    //         $orgImagePath = $request->file('OrganizationImage')->store('organization', 'spaces');

    //         if ($organization->OrganizationImage) {
    //             Storage::disk('spaces')->delete($organization->OrganizationImage);
    //         }
    //         $organization->OrganizationImage = $orgImagePath;
    //         $profilePictureUpdated = true;
    //     }

    //         $fieldsToUpdate = ['OrganizationName', 'OrganizationKhmerName', 'OrganizationContact', 'OrganizationRepresentative', 'OrganizationStreetNumber', 'OrganizationVillage', 'OrganizationSangkat', 'OrganizationKhan', 'OrganizationCity', 'AdminID', 'AccountStatusID'];
    //         foreach ($fieldsToUpdate as $field) {
    //             if ($request->has($field)) {
    //                 $changes[$field] = ['old' => $organization->$field, 'new' => $request->$field];
    //                 $organization->$field = $request->$field;
    //             }
    //         }

    //     $organization->save(); 


    //     $user = User::where('OrganizationID', $OrganizationID)->first();
    
    //     if ($user) {
    //         $nameUpdated = false;
    //         if ($request->has('OrganizationName')) {
    //             $user->name = $request->OrganizationName;
    //             $nameUpdated = true;
    //         }
            
    //         if ($nameUpdated) {
    //             $user->save();
    //         }
    //     }

    //     // Log the activity
    //     $requestUser = $request->user();
    //     $orgName = $organization->OrganizationName;

    //     if ($profilePictureUpdated && !empty($changes)) {
    //         // Format changes into a readable string
    //         $formattedChanges = [];
    //         foreach ($changes as $attribute => $change) {
    //             $formattedChanges[] = "{$attribute}: '{$change['old']}' to '{$change['new']}'";
    //         }
    //         $changesString = implode(', ', $formattedChanges);

    //         $activityMessage = "{$requestUser->name} updated details and changed the profile picture of organization {$orgName}. Changes: {$changesString}";
    //     } elseif ($profilePictureUpdated) {
    //         $activityMessage = "{$requestUser->name} changed the profile picture of organization {$orgName}";
    //     } elseif (!empty($changes)) {
    //         // Format changes into a readable string
    //         $formattedChanges = [];
    //         foreach ($changes as $attribute => $change) {
    //             $formattedChanges[] = "{$attribute}: '{$change['old']}' to '{$change['new']}'";
    //         }
    //         $changesString = implode(', ', $formattedChanges);

    //         $activityMessage = "{$requestUser->name} updated details of organization {$orgName}. Changes: {$changesString}";
    //     } else {
    //         $activityMessage = "{$requestUser->name} updated details of organization {$orgName}";
    //     }

    //     ActivityLog::create([
    //         'user_id' => $requestUser->id,
    //         'user_name' => $requestUser->name,
    //         'activity' => $activityMessage,
    //     ]);

    //     return response()->json([
    //         'message' => 'Organization updated successfully',
    //     ], 200);
    // } 
       
    public function updateorganization(Request $request, $OrganizationID) {
        $messages = [
            'OrganizationName.string' => 'Organization name must be a string.',
            'OrganizationKhmerName.string' => 'Organization Khmer name must be a string.',
            'OrganizationContact.regex' => 'Organization contact must be a string containing at least 9 numeric characters.',
            'OrganizationRepresentative.string' => 'Organization representative must be a string.',
            'OrganizationProofOfIdentity.mimes' => 'Organization proof of identity must be a PDF or DOC file.',
            'OrganizationProofOfIdentity.max' => 'Organization proof of identity must not exceed 10MB.',
            'OrganizationImage.image' => 'Organization image must be an image file.',
            'OrganizationImage.mimes' => 'Profile Picture must be a JPEG, PNG, JPG, or GIF file.',
            'OrganizationImage.max' => 'Profile picture must not exceed 10MB.',
            'OrganizationStreetNumber.string' => 'Street number must be a string.',
            'OrganizationVillage.string' => 'Village must be a string.',
            'OrganizationSangkat.string' => 'Sangkat must be a string.',
            'OrganizationKhan.string' => 'Khan must be a string.',
            'OrganizationCity.string' => 'City must be a string.',
            'OrganizationID.numeric' => 'Organization ID must be a number.',
            'AccountStatusID.numeric' => 'Account status ID must be a number.',

            // Add more custom error messages for other fields if needed
        ];
        $validator = Validator::make($request->all(), [
            'OrganizationName' => 'string',
            'OrganizationKhmerName' => 'string',
            'OrganizationContact' => 'string|regex:/^[0-9]{9,}$/',
            'OrganizationRepresentative' => 'string',
            'OrganizationProofOfIdentity' => 'nullable|file|mimes:pdf,doc|max:10240',
            'OrganizationImage' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
            'OrganizationStreetNumber' => 'numeric',
            'OrganizationVillage' => 'string',
            'OrganizationSangkat' => 'string',
            'OrganizationKhan' => 'string',
            'OrganizationCity' => 'string',
            'AccountStatusID'=> 'numeric',

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

        $organization = Organization::find($OrganizationID);

        if (!$organization) {
            return response()->json(['message' => 'Organization not found'], 404);
        }

        // Capture existing values before updating
        $existingValues = [];
        foreach ($organization->getAttributes() as $key => $value) {
            $existingValues[$key] = $value;
        }
    
        // Handle OrganizationImage update
        $profilePictureUpdated = false;

        if ($request->hasFile('OrganizationProofOfIdentity') && $request->file('OrganizationProofOfIdentity')->isValid() ) {

            $orgProofPath = $request->file('OrganizationProofOfIdentity')->store('organization', 'spaces');

            if ($organization->OrganizationProofOfIdentity) {
                Storage::disk('spaces')->delete($organization->OrganizationProofOfIdentity);
            }
            $organization->OrganizationProofOfIdentity = $orgProofPath;
        }

        if ($request->hasFile('OrganizationImage') && $request->file('OrganizationImage')->isValid() ) {

            $orgImagePath = $request->file('OrganizationImage')->store('organization', 'spaces');

            if ($organization->OrganizationImage) {
                Storage::disk('spaces')->delete($organization->OrganizationImage);
            }
            $organization->OrganizationImage = $orgImagePath;
            $profilePictureUpdated = true;
        }

            $fieldsToUpdate = ['OrganizationName', 'OrganizationKhmerName', 'OrganizationContact', 'OrganizationRepresentative', 'OrganizationStreetNumber', 'OrganizationVillage', 'OrganizationSangkat', 'OrganizationKhan', 'OrganizationCity', 'AdminID', 'AccountStatusID'];
            foreach ($fieldsToUpdate as $field) {
                if ($request->has($field)) {
                    $organization->$field = $request->$field;
                }
            }

        $organization->save(); 


        $user = User::where('OrganizationID', $OrganizationID)->first();
    
        if ($user) {
            $nameUpdated = false;
            if ($request->has('OrganizationName')) {
                $user->name = $request->OrganizationName;
                $nameUpdated = true;
            }
            
            if ($nameUpdated) {
                $user->save();
            }
        }

        // Log the activity
        $requestUser = $request->user();
        $orgName = "{$organization->OrganizationName}";
    
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
                    $organization->$field = $newValue;
        
                    // Log only if there's a change
                    if ($field === 'AccountStatusID') {
                        $oldStatus = $statusLabels[$oldValue] ?? 'unknown';
                        $newStatus = $statusLabels[$newValue] ?? 'unknown';
                        if ($oldStatus !== $newStatus) {
                            $activityMessage = "{$requestUser->name} changed status from '{$oldStatus}' to '{$newStatus}' for organization {$orgName}";
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
                $activityMessage = "{$requestUser->name} updated details and changed the profile picture of organization {$orgName}. Changes: {$changesString}";
            } else {
                $activityMessage = "{$requestUser->name} updated details of organization {$orgName}. Changes: {$changesString}";
            }
    
            ActivityLog::create([
                'user_id' => $requestUser->id,
                'user_name' => $requestUser->name,
                'activity' => $activityMessage,
            ]);
        } elseif ($profilePictureUpdated) {
            // If only profile picture updated without other changes
            $activityMessage = "{$requestUser->name} set a new profile picture for organization {$orgName}";
            ActivityLog::create([
                'user_id' => $requestUser->id,
                'user_name' => $requestUser->name,
                'activity' => $activityMessage,
            ]);
        }       

        return response()->json([
            'message' => 'Organization updated successfully',
        ], 200);
    } 

    public function getDaycareStaffParentByOrganizationId($organizationId)
    {
        // Fetch the organization
        $organization = Organization::findOrFail($organizationId);

        // Get all daycares of the organization
        $daycares = $organization->daycares;

        // Initialize arrays to store parent, staff, and daycare information
        $parentInformation = [];
        $staffInformation = [];
        $daycareInformation = [];

        // Loop through each daycare
        foreach ($daycares as $daycare) {
            // Get all parents of the daycare with daycare name
            $parents = $daycare->parents()->get();

            // Append daycare name to each parent
            foreach ($parents as $parent) {
                $parentData = $parent->toArray();
                $parentData['DaycareName'] = $daycare->DaycareName;
                $parentInformation[] = $parentData;
            }

            // Get all staff of the daycare with daycare name
            $staff = $daycare->staff()->get();

            // Append daycare name to each staff member
            foreach ($staff as $staffMember) {
                $staffMemberData = $staffMember->toArray();
                $staffMemberData['DaycareName'] = $daycare->DaycareName;
                $staffInformation[] = $staffMemberData;
            }

            // Add daycare information with organization name
            $daycareData = $daycare->toArray();
            $daycareData['OrganizationName'] = $organization->OrganizationName;
            $daycareInformation[] = $daycareData;
        }

        // Return the combined information
        return response()->json([
            'parents' => $parentInformation,
            'staff' => $staffInformation,
            'daycares' => $daycareInformation,
        ]);
    }


    public function getUsersByOrganizationId($organizationId)
    {
        // Find the organization
        $organization = Organization::find($organizationId);

        if (!$organization) {
            return response()->json(['error' => 'Organization not found'], 404);
        }

        // Eager load daycares with their users and organization users with their roles
        $organization->load(['daycares.users.roles', 'users.roles']);

        // Initialize arrays to store user information
        $userInformation = [];

        // Iterate over each user directly associated with the organization
        foreach ($organization->users as $user) {
            $userInformation[$user->id] = [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'email' => $user->email,
                'daycare_name' => null, // These users are not associated with a specific daycare
                'daycare_id' => null,
                'organization_id' => $organization->OrganizationID,
                'accountstatus' => $user->AccountStatusID,
                'roles' => $user->roles->pluck('name')->toArray(),
            ];
        }

        // Iterate over each daycare
        foreach ($organization->daycares as $daycare) {
            // Get all users of the daycare
            foreach ($daycare->users as $user) {
                if (isset($userInformation[$user->id])) {
                    // Merge roles if the user is already in the list
                    $userInformation[$user->id]['roles'] = array_unique(array_merge($userInformation[$user->id]['roles'], $user->roles->pluck('name')->toArray()));
                } else {
                    $userInformation[$user->id] = [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'email' => $user->email,
                        'daycare_name' => $daycare->DaycareName,
                        'daycare_id' => $daycare->DaycareID,
                        'organization_id' => $organization->OrganizationID,
                        'accountstatus' => $user->AccountStatusID,
                        'roles' => $user->roles->pluck('name')->toArray(),
                    ];
                }
            }
        }

        // Define the order of the roles
        $roleOrder = [
            'organization superadmin' => 1,
            'organization admin' => 2,
            'daycare superadmin' => 3,
            'daycare admin' => 4,
            'daycare staff' => 5,
            'daycare parent' => 6,
        ];

        // Custom sort function based on the role order
        usort($userInformation, function ($a, $b) use ($roleOrder) {
            $aMinRole = min(array_map(fn($role) => $roleOrder[$role] ?? 999, $a['roles']));
            $bMinRole = min(array_map(fn($role) => $roleOrder[$role] ?? 999, $b['roles']));

            return $aMinRole <=> $bMinRole;
        });

        // Return the combined information
        return response()->json(array_values($userInformation));
    }


    public function getalldatafrommyOrg($organizationId)
    {
        $daycares = Daycare::where('OrganizationID', $organizationId)
            ->withCount([
                'parents',
                'parents as active_parents_count' => function ($query) {
                    $query->where('parent.AccountStatusID', 1);
                },
                'parents as inactive_parents_count' => function ($query) {
                    $query->where('parent.AccountStatusID', 2);
                },
                'staff',
                'staff as active_staff_count' => function ($query) {
                    $query->where('staff.AccountStatusID', 1);
                },
                'staff as inactive_staff_count' => function ($query) {
                    $query->where('staff.AccountStatusID', 2);
                },
                'children',
                'children as active_children_count' => function ($query) {
                    $query->where('child.AccountStatusID', 1);
                },
                'children as inactive_children_count' => function ($query) {
                    $query->where('child.AccountStatusID', 2);
                },
               
            ])
            ->get();

            

        return response()->json($daycares);
    }
}
