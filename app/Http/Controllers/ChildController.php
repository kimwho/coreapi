<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Child;
use App\Models\Parents;
use App\Models\Organization;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use DB;

class ChildController extends Controller
{
    
    public function index(){
        $children = Child::with('parent')
                        ->get()
                        ->map(function($child) {
                            $childArr = $child->toArray();
                            $childArr['Parent'] = $child->parent;
                            $childArr['DaycareName'] = $child->parent->daycare->DaycareName;
                            unset($childArr['parent']); // Remove the nested parent array
                            return $childArr;
                        });
        
        return response()->json($children);
    }

    public function childwithdaycareid($daycareId)
    {
        $children = Child::with('parent')
            ->whereHas('parent', function ($query) use ($daycareId) {
                $query->where('DaycareID', $daycareId);
            })
            ->get();

        $childrenCount = $children->count();

        // Return the children and their count as JSON response
        return response()->json([
            'children' => $children,
            'children_count' => $childrenCount
        ]);
    }

    public function show($id){
        $child = Child::with('parent')->findOrFail($id);
        return response()->json($child);
    }

    public function showChildrenByParentID($parentID){
            // Retrieve the parent with the given ParentID
            $parent = Parents::find($parentID);

            if (!$parent) {
                return response()->json(['error' => 'Parent not found'], 404);
            }

            // Retrieve all children belonging to the parent
            $children = $parent->children;

            return response()->json(['children' => $children]);
    }
      
    public function store(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'ChildFirstName' => 'required|string',
            'ChildLastName' => 'required|string',
            'ChildKhmerFirstName' => 'required|string',
            'ChildKhmerLastName' => 'required|string',
            'ChildDOB' => 'required|date',
            'SexID' => 'required|numeric',
            'ParentID' => 'required|numeric',
            'ChildImage' => 'required|image|mimes:jpeg,png,jpg,gif|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        if ($request->hasFile('ChildImage') && $request->file('ChildImage')->isValid()) {
            // Handle Child image upload
            $childimagepath = $request->file('ChildImage')->store('child', 'spaces');

            $Child = new Child;
            $Child->ChildFirstName = $request->ChildFirstName;
            $Child->ChildLastName = $request->ChildLastName;
            $Child->ChildKhmerFirstName = $request->ChildKhmerFirstName;
            $Child->ChildKhmerLastName = $request->ChildKhmerLastName;
            $Child->ChildDOB = $request->ChildDOB;
            $Child->SexID = $request->SexID;
            $Child->ParentID = $request->ParentID;
            $Child->ChildImage = $childimagepath;

            $Child->save();

            // Log the activity
            $user = $request->user();
            ActivityLog::create([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'activity' => "{$user->name} registered a new child in Daycare",
            ]);

            return response()->json([
                'message' => 'Child Added',
            ], 201);
        }
    }

    public function update(Request $request, $ChildID)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'ChildFirstName' => 'string',
            'ChildLastName' => 'string',
            'ChildKhmerFirstName' => 'string',
            'ChildKhmerLastName' => 'string',
            'ChildDOB' => 'date',
            'SexID' => 'numeric',
            'ParentID' => 'numeric',
            'childtypeID' => 'numeric',
            'AccountStatusID' => 'numeric',
            'ChildImage' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }
    
        $child = Child::find($ChildID);
    
        if (!$child) {
            return response()->json(['message' => 'Child not found'], 404);
        }
    
        //changes
        // Capture existing values before updating
        $existingValues = [];
        foreach ($child->getAttributes() as $key => $value) {
            $existingValues[$key] = $value;
        }
        // Handle ParentImage update
        $profilePictureUpdated = false;
        if ($request->hasFile('ChildImage') && $request->file('ChildImage')->isValid()) {
            // Handle Child image upload
            $childImagePath = $request->file('ChildImage')->store('child', 'spaces');
            // Delete old image if exists
            if ($child->ChildImage) {
                Storage::disk('spaces')->delete($child->ChildImage);
            }
            $child->ChildImage = $childImagePath;
            $profilePictureUpdated = true;
        }
    
        // Update child fields if they are present in the request
        $fieldsToUpdate = ['ChildFirstName', 'ChildLastName', 'ChildKhmerFirstName', 'ChildKhmerLastName', 'ChildDOB', 'SexID', 'ParentID', 'childtypeID', 'AccountStatusID'];
        foreach ($fieldsToUpdate as $field) {
            if ($request->has($field)) {
                $child->$field = $request->$field;
            }
        }
    
        $child->save();   
    
        // Log the activity
        $requestUser = $request->user();
        $childName = "{$child->ChildFirstName} {$child->ChildLastName}";
    
        // Initialize formatted changes array
        $formattedChanges = [];
    
        // Define readable values for specific fields
        $statusLabels = [
            1 => 'active',
            2 => 'inactive'
        ];

        $typeLabels = [
            1 => 'student',
            2 => 'alumni'
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
                    $child->$field = $newValue;
        
                    // Log only if there's a change
                    if ($field === 'AccountStatusID') {
                        $oldStatus = $statusLabels[$oldValue] ?? 'unknown';
                        $newStatus = $statusLabels[$newValue] ?? 'unknown';
                        if ($oldStatus !== $newStatus) {
                            $activityMessage = "{$requestUser->name} changed status from '{$oldStatus}' to '{$newStatus}' for child {$childName}";
                            ActivityLog::create([
                                'user_id' => $requestUser->id,
                                'user_name' => $requestUser->name,
                                'activity' => $activityMessage,
                            ]);
                        }
                    }
                    elseif ($field === 'childtypeID') {
                        $oldStatus = $typeLabels[$oldValue] ?? 'unknown';
                        $newStatus = $typeLabels[$newValue] ?? 'unknown';
                        if ($oldStatus !== $newStatus) {
                            $activityMessage = "{$requestUser->name} changed student type from '{$oldStatus}' to '{$newStatus}' for child {$childName}";
                            ActivityLog::create([
                                'user_id' => $requestUser->id,
                                'user_name' => $requestUser->name,
                                'activity' => $activityMessage,
                            ]);
                        }
                    } 
                    elseif ($field === 'SexID') {
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
             $activityMessage = "{$requestUser->name} updated details and changed the profile picture of child {$childName}. Changes: {$changesString}";
         } else {
             $activityMessage = "{$requestUser->name} updated details of child {$childName}. Changes: {$changesString}";
         }
 
         ActivityLog::create([
             'user_id' => $requestUser->id,
             'user_name' => $requestUser->name,
             'activity' => $activityMessage,
         ]);
     } elseif ($profilePictureUpdated) {
         // If only profile picture updated without other changes
         $activityMessage = "{$requestUser->name} set a new profile picture for {$childName}";
         ActivityLog::create([
             'user_id' => $requestUser->id,
             'user_name' => $requestUser->name,
             'activity' => $activityMessage,
         ]);
     }        

        
    
        return response()->json([
            'message' => 'Child updated successfully',
        ], 200);
    }
        
    public function activateChildAccount(Request $request, $childId)
    {
        // Find the child
        $child = Child::find($childId);
    
        if (!$child) {
            return response()->json(['message' => 'Child not found'], 404);
        }
    
        // Activate the account (set AccountStatusID to 1)
        $child->AccountStatusID = 1;
        $child->save();
    
        $requestUser = $request->user(); // Assuming you have authenticated user
        ActivityLog::create([
            'user_id' => $requestUser->id,
            'user_name' => $requestUser->name,
            'activity' => "{$requestUser->name} activated {$child->ChildFirstName} {$child->ChildLastName}'s account. ",
        ]);

        return response()->json(['message' => 'Child activated successfully'], 200);
    }
    
    public function deactivateChildAccount(Request $request, $childId)
    {
        // Find the child
        $child = Child::find($childId);
    
        if (!$child) {
            return response()->json(['message' => 'Child not found'], 404);
        }
    
        // Activate the account (set AccountStatusID to 1)
        $child->AccountStatusID = 2;
        $child->save();
    
        $requestUser = $request->user(); // Assuming you have authenticated user
        ActivityLog::create([
            'user_id' => $requestUser->id,
            'user_name' => $requestUser->name,
            'activity' => "{$requestUser->name} deactivated {$child->ChildFirstName} {$child->ChildLastName}'s account. ",
        ]);

        return response()->json(['message' => 'Child deactivated successfully'], 200);
    }

    // public function getchildByOrganizationId($organizationId){
    //     // Fetch the organization
    //     $organization = Organization::findOrFail($organizationId);
    
    //     // Get all daycares of the organization
    //     $daycares = $organization->daycares;
    
    //     // Initialize an empty array to store child information
    //     $childInformation = [];
    
    //     // Loop through each daycare and fetch its children
    //     foreach ($daycares as $daycare) {
    //         // Get all children of the daycare with parents and daycare name
    //         $children = Child::whereHas('parent', function ($query) use ($daycare) {
    //             // Filter parents by daycare ID
    //             $query->where('DaycareID', $daycare->DaycareID);
    //         })->with('parent')->get();
    
    //         // Append daycare name to each child
    //         foreach ($children as $child) {
    //             $childData = $child->toArray();
    //             $childData['DaycareName'] = $daycare->DaycareName;
    //             $childInformation[] = $childData;
    //         }
    //     }
    
    //     // Return child information
    //     return response()->json($childInformation);
    // }

    public function getChildByOrganizationId($organizationId)
    {
        // Fetch the organization
        $organization = Organization::findOrFail($organizationId);

        // Get all daycares of the organization
        $daycares = $organization->daycares;

        // Initialize an empty array to store child information
        $childInformation = [];

        // Initialize a counter for the total number of children
        $totalChildCount = 0;

        // Loop through each daycare and fetch its children
        foreach ($daycares as $daycare) {
            // Get all children of the daycare with parents and daycare name
            $children = Child::whereHas('parent', function ($query) use ($daycare) {
                // Filter parents by daycare ID
                $query->where('DaycareID', $daycare->DaycareID);
            })->with('parent')->get();

            // Increment the total child count
            $totalChildCount += $children->count();

            // Append daycare name to each child
            foreach ($children as $child) {
                $childData = $child->toArray();
                $childData['DaycareName'] = $daycare->DaycareName;
                $childInformation[] = $childData;
            }
        }

        // Add the total child count to the response
        $response = [
            'totalChildCount' => $totalChildCount,
            'childInformation' => $childInformation
        ];

        // Return child information
        return response()->json($response);
    }
    
    public function getActiveChildrenByOrganizationId($organizationId)
    {
        // Fetch the organization
        $organization = Organization::findOrFail($organizationId);
    
        // Get all daycares of the organization
        $daycares = $organization->daycares;
    
        // Initialize an empty array to store child information
        $childInformation = [];
    
        // Initialize a counter for the total number of active children
        $totalActiveChildCount = 0;
    
        // Loop through each daycare and fetch its active children
        foreach ($daycares as $daycare) {
            // Get all active children of the daycare with parents
            $children = Child::whereHas('parent', function ($query) use ($daycare) {
                // Filter parents by daycare ID
                $query->where('DaycareID', $daycare->DaycareID);
            })->where('AccountStatusID', 1)->with('parent')->get();
    
            // Increment the total active child count
            $totalActiveChildCount += $children->count();
    
            // Append daycare name to each active child
            foreach ($children as $child) {
                $childData = $child->toArray();
                $childData['DaycareName'] = $daycare->DaycareName;
                $childInformation[] = $childData;
            }
        }
    
        // Prepare the response with total count and child information
        $response = [
            'totalActiveChildCount' => $totalActiveChildCount,
            'activeChildInformation' => $childInformation
        ];
    
        // Return child information with AccountStatusID 1 and total count
        return response()->json($response);
    }
    
    
    public function getInactiveChildrenByOrganizationId($organizationId)
    {
        // Fetch the organization
        $organization = Organization::findOrFail($organizationId);
    
        // Get all daycares of the organization
        $daycares = $organization->daycares;
    
        // Initialize an empty array to store child information
        $childInformation = [];
    
        // Initialize a counter for the total number of inactive children
        $totalInactiveChildCount = 0;
    
        // Loop through each daycare and fetch its inactive children
        foreach ($daycares as $daycare) {
            // Get all inactive children of the daycare with parents
            $children = Child::whereHas('parent', function ($query) use ($daycare) {
                // Filter parents by daycare ID
                $query->where('DaycareID', $daycare->DaycareID);
            })->where('AccountStatusID', 2)->with('parent')->get();
    
            // Increment the total inactive child count
            $totalInactiveChildCount += $children->count();
    
            // Append daycare name to each inactive child
            foreach ($children as $child) {
                $childData = $child->toArray();
                $childData['DaycareName'] = $daycare->DaycareName;
                $childInformation[] = $childData;
            }
        }
    
        // Prepare the response with total count and child information
        $response = [
            'totalInactiveChildCount' => $totalInactiveChildCount,
            'inactiveChildInformation' => $childInformation
        ];
    
        // Return child information with AccountStatusID 2 and total count
        return response()->json($response);
    }
    
    
    public function getChildrenByDaycareIDAndStatusActive($daycareID)
    {
        $children = Child::with('parent')
            ->whereHas('parent', function($query) use ($daycareID) {
                $query->where('DaycareID', $daycareID);
            })
            ->where('AccountStatusID', 1)
            ->get();
    
        return $this->formatResponse($children, 'active_children');
    }
    
    public function getChildrenByDaycareIDAndStatusInactive($daycareID)
    {
        $children = Child::with('parent')
            ->whereHas('parent', function($query) use ($daycareID) {
                $query->where('DaycareID', $daycareID);
            })
            ->where('AccountStatusID', 2)
            ->get();
    
        return $this->formatResponse($children, 'inactive_children');
    }
    
    private function formatResponse($data, $key)
    {
        if ($data->isEmpty()) {
            return response()->json([
                $key => [],
                "{$key}_count" => 0,
                'message' => "No {$key} found for the specified criteria"
            ], 404);
        }

        return response()->json([
            $key => $data,
            "{$key}_count" => $data->count()
        ]);
    }

    public function countGraduateStudents()
    {
        $counts = Child::select(
            DB::raw('YEAR(childtype_changed_at) as year'),
            DB::raw('MONTH(childtype_changed_at) as month'),
            DB::raw('COUNT(*) as count')
        )
        ->where('childtypeID', 2)
        ->whereNotNull('childtype_changed_at')
        ->groupBy('year', 'month')
        ->orderBy('year', 'asc')
        ->orderBy('month', 'asc')
        ->get();

    return response()->json($counts);
    }

    // Count changes to childtypeID 2 grouped by DaycareID and month
    public function countChildtypeIDChangesByDaycare($daycareID)
    {
        $counts = Child::select(
                DB::raw('YEAR(childtype_changed_at) as year'),
                DB::raw('MONTH(childtype_changed_at) as month'),
                DB::raw('COUNT(*) as count')
            )
            ->join('parent', 'child.ParentID', '=', 'parent.ParentID')
            ->where('child.childtypeID', 2)
            ->where('parent.DaycareID', $daycareID)
            ->whereNotNull('child.childtype_changed_at')
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();

        return response()->json($counts);
    }

    // Count changes to childtypeID 2 grouped by OrganizationID and month
    public function countChildtypeIDChangesByOrganization($organizationID)
    {
        $counts = Child::select(
                DB::raw('YEAR(childtype_changed_at) as year'),
                DB::raw('MONTH(childtype_changed_at) as month'),
                DB::raw('COUNT(*) as count')
            )
            ->join('parent', 'child.ParentID', '=', 'parent.ParentID')
            ->join('daycare', 'parent.DaycareID', '=', 'daycare.DaycareID')
            ->where('child.childtypeID', 2)
            ->where('daycare.OrganizationID', $organizationID)
            ->whereNotNull('child.childtype_changed_at')
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();

        return response()->json($counts);
    }
    
}

    
