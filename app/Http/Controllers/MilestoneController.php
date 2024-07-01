<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Milestone;
use App\Models\Child;
use App\Models\Staff;
use App\Models\Images;
use App\Models\User;
use App\Models\Videos;
use App\Models\Reports;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use DateTime;

class MilestoneController extends Controller
{

    public function showallmilestones(){
        // Retrieve all milestones
        $milestones = Milestone::all();
        return response()->json(['milestones' => $milestones]);
    }
    
    public function countMilestonesToday(Request $request, $daycareID){

        // Get the current date
        $currentDate = Carbon::now()->toDateString();

        // Count the milestones for the daycare for the current date
        $milestoneCount = Milestone::whereHas('user', function ($query) use ($daycareID) {
                $query->where('DaycareID', $daycareID);
            })
            ->whereDate('created_at', $currentDate)
            ->count();

        // If no milestones found, return zero
        $milestoneCount = $milestoneCount ?? 0;

        return response()->json(['milestone_count' => $milestoneCount]);
    }

    public function countMilestonesForaWeek(Request $request, $daycareID){
        // Get the start and end dates for the week
        $startDate = Carbon::now()->startOfWeek()->toDateString();
        $endDate = Carbon::now()->endOfWeek()->toDateString();
    
        // Count the milestones for the daycare for the current week
        $milestoneCount = Milestone::whereHas('user', function ($query) use ($daycareID) {
                $query->where('DaycareID', $daycareID);
            })
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->count();
    
        // If no milestones found, return zero
        $milestoneCount = $milestoneCount ?? 0;
    
        return response()->json(['milestone_count' => $milestoneCount]);
    }

    public function countMilestonesTodayWithLikes(Request $request, $daycareID){
        // Get the current date
        $currentDate = now()->toDateString();
        
        // Count the milestones for the daycare for the current date where Likes is 1
        $milestoneCount = Milestone::whereHas('user', function ($query) use ($daycareID) {
                $query->where('DaycareID', $daycareID);
            })
            ->where('Likes', 1)
            ->whereDate('created_at', $currentDate)
            ->count();
    
        return response()->json(['milestone_count' => $milestoneCount]);
    }

    public function countMilestonesForWeekWithLikes(Request $request, $daycareID){
        // Get the start and end dates for the week
        $startDate = now()->startOfWeek()->toDateString();
        $endDate = now()->endOfWeek()->toDateString();
    
        // Count the milestones for the daycare for the current week where Likes is 1
        $milestoneCount = Milestone::whereHas('user', function ($query) use ($daycareID) {
                $query->where('DaycareID', $daycareID);
            })
            ->where('Likes', 1)
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->count();
    
        return response()->json(['milestone_count' => $milestoneCount]);
    }
    
    public function show($id)
    {
        // Retrieve the milestone with the given MilestoneID along with its images and videos
        $milestone = Milestone::with(['images' => function ($query) {
            $query->select('ImageID', 'ImagePath', 'MilestoneID'); // Include ImageID for the response
        }, 'videos' => function ($query) {
            $query->select('VideoID', 'VideoPath', 'MilestoneID'); // Include VideoID for the response
        }])
        ->where('MilestoneID', $id)
        ->whereNull('deleted_at') // Exclude soft-deleted milestones
        ->first();
    
        if (!$milestone) {
            return response()->json(['error' => 'Milestone not found or may have been deleted'], 404);
        }
    
        // Prepare the response
        $response = [
            'MilestoneID' => $milestone->MilestoneID,
            'Description' => $milestone->Description,
            'Likes' => $milestone->Likes,
            'ChildID' => $milestone->ChildID,
            'images' => $milestone->images->map(function ($image) {
                return [
                    'ImageID' => $image->ImageID,
                    'ImagePath' => $image->ImagePath
                ];
            })->toArray(),
            'videos' => $milestone->videos->map(function ($video) {
                return [
                    'VideoID' => $video->VideoID,
                    'VideoPath' => $video->VideoPath
                ];
            })->toArray()
        ];
    
        return response()->json(['milestone' => $response]);
    }
    
    public function getMilestoneWithMediabyChildID(Request $request, $ChildID){
        // Check if milestones exist for the given ChildID
        $milestones = Milestone::where('ChildID', $ChildID)
            ->orderBy('created_at', 'asc') // Order by creation date ascending
            ->get();
        // Retrieve child information
        $child = Child::find($ChildID);
        $ChildFirstName = $child->ChildFirstName;
        $ChildLastName = $child->ChildLastName;
        // Initialize an array to hold the response
        $response = [];
    
        // Check if milestones exist
        if ($milestones->isEmpty()) {
            return response()->json([
                'child_id' => $ChildID,
                'child_first_name' => $ChildFirstName,
                'child_last_name' => $ChildLastName,
                'message' => 'No milestone yet'
            ], 200);
        }
    
        // Extract the child ID from the first milestone record
        $childId = $milestones->first()->ChildID;
    
        // Loop through milestones to construct the response
        foreach ($milestones as $milestone) {
            // Retrieve images associated with the milestone
            $images = Images::where('MilestoneID', $milestone->MilestoneID)->pluck('ImagePath')->toArray();
    
            // Retrieve videos associated with the milestone
            $videos = Videos::where('MilestoneID', $milestone->MilestoneID)->pluck('VideoPath')->toArray();
    
            // Retrieve child information
            $child = Child::find($milestone->ChildID);
    
            // Format created_at date with month in text
            $created_at_formatted = Carbon::parse($milestone->created_at)->format('d F Y');
    
            // Prepare the response for each milestone
            $response[] = [
                'id' => $milestone->MilestoneID,
                'description' => $milestone->Description,
                'likes' => $milestone->Likes,
                'staff_id' => $milestone->StaffID,
                'child_id' => $milestone->ChildID,
                'child_first_name' => $child->ChildFirstName,
                'child_last_name' => $child->ChildLastName,
                'created_at' => $created_at_formatted,
                'images' => $images,
                'videos' => $videos
            ];
        }
    
        // Return the response along with the child ID
        return response()->json([
            'child_id' => $childId,
            'child_first_name' => $child->ChildFirstName,
            'child_last_name' => $child->ChildLastName,
            'milestones' => $response
        ], 200);
    }
     
    public function updateLikes(Request $request, $id){
        // Find the milestone by its ID
        $milestone = Milestone::find($id);
            
        // Retrieve the child information
        $child = $milestone->child;

        if (!$milestone) {
            return response()->json(['message' => 'Milestone not found'], 404);
        }

        // Update the likes to 1
        $milestone->update(['Likes' => 1]);

        // Log the activity
        $user = $request->user();
        $childName = $child ? "{$child->ChildFirstName} {$child->ChildLastName}" : "Unknown Child";
        ActivityLog::create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'activity' => "{$user->name} liked a milestone of their child {$childName}: {$milestone->Description}",
        ]);

        return response()->json(['message' => 'Likes updated successfully', 'milestone' => $milestone]);
    }
 
    public function create(Request $request){
        try {
            // Define custom error messages
            $messages = [
                'Description.required' => 'Description is required.',
                'Description.string' => 'Description must be a string.',
                'ChildID.required' => 'ChildID is required.',
                'ChildID.integer' => 'ChildID must be an integer.',
                'UserID.required' => 'UserID is required.',
                'UserID.integer' => 'UserID must be an integer.',
                'images.required' => 'At least one image is required.',
                'images.*.image' => 'Each file must be an image.',
                'images.*.mimes' => 'Each image must be a file of type: jpeg, png, jpg, webp.',
                'images.*.max' => 'Each image must not exceed 40000 KB.',
                'videos.*.mimes' => 'Each video must be a file of type: mp4.',
                'videos.*.max' => 'Each video must not exceed 40000 KB.',
            ];
    
            // Manually create the validator
            $validator = Validator::make($request->all(), [
                'Description' => 'required|string',
                'ChildID' => 'required|integer',
                'UserID' => 'required|integer',
                'images' => 'required', // Check if images array is present
                'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:40000',
                'videos.*' => 'nullable|mimes:mp4|max:40000',
            ], $messages);
    
            // Validate the request
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

            // Retrieve the Child record using ChildID
            $child = Child::find($validatedData['ChildID']);
            if (!$child) {
                return response()->json(['message' => 'Child not found'], 404);
            }
    
            // Create a new Milestone instance
            $milestone = new Milestone();
            $milestone->Description = $validatedData['Description'];
            $milestone->ChildID = $validatedData['ChildID'];
            $milestone->UserID = $validatedData['UserID'];
    
            // Save the Milestone to the database
            $milestone->save();
    
            // Ensure the MilestoneID is properly retrieved after saving the milestone
            $milestoneID = $milestone->getAttribute('MilestoneID');
    
            // Handle image uploads
            foreach ($request->file('images') as $image) {
                $imagePath = $image->store('milestone', 'spaces');
                // Save the image path to the database along with the MilestoneID
                Images::create([
                    'MilestoneID' => $milestoneID,
                    'ImagePath' => $imagePath
                ]);
            }
    
            // Handle video uploads if videos are present
            if ($request->hasFile('videos')) {
                foreach ($request->file('videos') as $key => $video) {
                    try {
                        $videoPath = $video->store('milestone', 'spaces');
                        // Save the video path to the database along with the MilestoneID
                        Videos::create([
                            'MilestoneID' => $milestoneID,
                            'VideoPath' => $videoPath
                        ]);
                    } catch (\Exception $e) {
                        // Return the exception message as an error response
                        return response()->json(['success' => false, 'message' => "The video at index $key failed to upload. Error: " . $e->getMessage()], 500);
                    }
                }
            }
    
            // Log the activity
            $user = $request->user();
            ActivityLog::create([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'activity' => "{$user->name} create a new milestone for {$child->ChildFirstName} {$child->ChildLastName}",
            ]);

            // Return a success response
            return response()->json(['success' => true, 'message' => 'Milestone added successfully'], 201);
        } catch (\Exception $e) {
            // Return the exception message as an error response
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function delete(Request $request, $id)
    {
        try {
            // Find the milestone by ID
            $milestone = Milestone::findOrFail($id);
            
            // Retrieve the child information
            $child = $milestone->child;

            // Set the deleted_by field for the milestone
            $milestone->deleted_by = $request->user()->id;
            $milestone->save();
            
            // Soft delete the milestone's images and set the deleted_by field
            $milestone->images()->each(function ($image) use ($request) {
                $image->deleted_by = $request->user()->id;
                $image->save();
                $image->delete();
            });
            
            // Soft delete the milestone's videos and set the deleted_by field
            $milestone->videos()->each(function ($video) use ($request) {
                $video->deleted_by = $request->user()->id;
                $video->save();
                $video->delete();
            });
            
            // Log the activity
            $user = $request->user();
            $childName = $child ? "{$child->ChildFirstName} {$child->ChildLastName}" : "Unknown Child";
            ActivityLog::create([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'activity' => "{$user->name} deleted a milestone of {$childName}: {$milestone->Description}",
            ]);

            // Soft delete the milestone record itself
            $milestone->delete();

            

            // Return a success response
            return response()->json(['message' => 'Milestone deleted successfully'], 200);
        } catch (\Exception $e) {
            // Return an error response if something goes wrong
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getDeletedMilestonesByDaycare($daycareID)
    {
        if (!$daycareID) {
            return response()->json(['error' => 'DaycareID is required'], 400);
        }

        $deletedMilestones = Milestone::onlyTrashed()
            ->whereHas('child.parent', function ($query) use ($daycareID) {
                $query->where('DaycareID', $daycareID);
            })
            ->with(['images', 'videos', 'user', 'child', 'deletedByUser'])
            ->get();

        return response()->json($deletedMilestones);
    }

    public function showDeletedMilestone($id)
    {
        // Retrieve the soft-deleted milestone with the given MilestoneID along with its images, videos, child, and deletedByUser
        $milestone = Milestone::onlyTrashed()
            ->with([
                'images' => function ($query) {
                    $query->withTrashed()->select('ImageID', 'ImagePath', 'MilestoneID'); // Include ImageID for the response
                }, 
                'videos' => function ($query) {
                    $query->withTrashed()->select('VideoID', 'VideoPath', 'MilestoneID'); // Include VideoID for the response
                },
                'deletedByUser' => function ($query) {
                    $query->select('id', 'name'); // Include relevant user information
                },
                'child' => function ($query) {
                    $query->select('ChildID', 'ChildFirstName', 'ChildLastName'); // Include child's first name and last name
                }
            ])
            ->where('MilestoneID', $id)
            ->first();
    
        if (!$milestone) {
            return response()->json(['error' => 'Milestone not found or may not have been deleted'], 404);
        }
    
        // Prepare the response
        $response = [
            'MilestoneID' => $milestone->MilestoneID,
            'Description' => $milestone->Description,
            'Likes' => $milestone->Likes,
            'StaffID' => $milestone->StaffID,
            'ChildID' => $milestone->ChildID,
            'ChildFirstName' => $milestone->child->ChildFirstName,
            'ChildLastName' => $milestone->child->ChildLastName,
            'deleted_by' => $milestone->deleted_by,
            'deleted_by_user' => [
                'id' => $milestone->deletedByUser->id,
                'name' => $milestone->deletedByUser->name
            ],
            'images' => $milestone->images->map(function ($image) {
                return [
                    'ImageID' => $image->ImageID,
                    'ImagePath' => $image->ImagePath
                ];
            })->toArray(),
            'videos' => $milestone->videos->map(function ($video) {
                return [
                    'VideoID' => $video->VideoID,
                    'VideoPath' => $video->VideoPath
                ];
            })->toArray()
        ];
    
        return response()->json(['milestone' => $response]);
    }

    public function restore(Request $request, $id)
    {
        try {
            // Restore the milestone by ID
            $milestone = Milestone::onlyTrashed()->findOrFail($id);
            $milestone->deleted_by = null;
            $milestone->restore();
            
            // Retrieve the child information
            $child = $milestone->child;

            // Restore the milestone's images and remove the deleted_by field
            $milestone->images()->onlyTrashed()->each(function ($image) {
                $image->deleted_by = null;
                $image->restore();
            });
            
            // Restore the milestone's videos and remove the deleted_by field
            $milestone->videos()->onlyTrashed()->each(function ($video) {
                $video->deleted_by = null;
                $video->restore();
            });

            // Log the activity
            $user = $request->user();
            $childName = $child ? "{$child->ChildFirstName} {$child->ChildLastName}" : "Unknown Child";
            ActivityLog::create([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'activity' => "{$user->name} recover a milestone of {$childName}: {$milestone->Description}",
            ]);

            // Return a success response
            return response()->json(['message' => 'Milestone restored successfully'], 200);
        } catch (\Exception $e) {
            // Return an error response if something goes wrong
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function createAndGeneratePDF(Request $request){
        try {
            // Validate the incoming request for report creation
            $validatedData = $request->validate([
                'ChildID' => 'required|integer',
                'UserID' => 'required|integer',
            ]);

            // Generate and save PDF report
            $reportFilePath = $this->generatePDFReport($validatedData['UserID'], $validatedData['ChildID']);

            // Create a new Report instance
            $report = Reports::create([
                'UserID' => $validatedData['UserID'],
                'ChildID' => $validatedData['ChildID'],
                'ReportPath' => $reportFilePath
            ]);

            // Retrieve the child information
            $child = Child::findOrFail($validatedData['ChildID']);
            $childName = "{$child->ChildFirstName} {$child->ChildLastName}";

            // Log the activity
            $user = $request->user();
            ActivityLog::create([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'activity' => "{$user->name} generated a PDF form of milestone for {$childName}",
            ]);

            // Return a response
            return response()->json(['message' => 'Report generated successfully'], 201);
        } catch (\Exception $e) {
            // Return the exception message as an error response
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function generatePDFReport($userID, $childID){
        // Retrieve child details
        $child = Child::findOrFail($childID);
    
        // Retrieve milestones with images based on the provided Child ID
        $milestones = Milestone::with('images')->where('ChildID', $childID)->get();
    
        // Get staff name
        $userName = User::findOrFail($userID)->getFullName(); // Assuming you have a method to get the full name of the user
    
        // Initialize Dompdf with options
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true); // Enable remote file access
        $dompdf = new Dompdf($options);
    
        $html = '<!DOCTYPE html>
            <html>
            <head>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        position: relative;
                        min-height: 100vh;
                        margin: 0;
                        padding-bottom: 100px; /* Space for footer */
                    }
                    .milestone {
                        margin-bottom: 20px;
                    }
                    .description {
                        font-weight: bold;
                        margin-top: 10px; /* Add some space between image and description */
                    }
                    .image-container {
                        max-width: 54%;
                        height: auto%;
                        float: left;
                        margin-right: 2%;
                        margin-bottom: 10px;
                        border-radius: 10px;
                        overflow: hidden;
                    }
                    .image {
                        width: 100%; /* Ensure the image fits within the container */
                        height: auto;
                    }
                    .clear {
                        clear: both;
                    }
                    .footer {
                        position: absolute;
                        bottom: 0;
                        right: 0;
                        text-align: right;
                        width: 100%;
                        padding: 10px;
                        box-sizing: border-box;
                    }
                    .footer strong {
                        display: block;
                    }
                </style>
            </head>
            <body>';
    
            // Child name and User name
            $html .= '<h2>Child Name: ' . $child->ChildFirstName . ' ' . $child->ChildLastName . '</h2>';
    
            foreach ($milestones as $milestone) {
                $html .= '<div class="milestone">';
    
                // Format the date
                $createdAt = Carbon::parse($milestone->created_at);
                $formattedDate = $createdAt->format('d F Y');
    
                // Use the formatted date in your HTML output
                $html .= '<h5 class="created_at">' . $formattedDate . '</h5>';
    
                // Iterate through images
                foreach ($milestone->images as $image) {
                    // Adjust image path
                    $imagePath = $image->ImagePath;
                    // Get image URL from DigitalOcean Spaces
                    $imageUrl = Storage::disk('spaces')->url($imagePath);
    
                    // Wrap image in a container
                    $html .= '<div class="image-container">';
                    $html .= '<img src="' . $imageUrl . '" class="image">';
                    $html .= '<p class="description">Description: ' . $milestone->Description . '</p>';
                    $html .= '</div>';
                }
                $html .= '<div class="clear"></div>'; // Clear float
                $html .= '</div>';
            }
    
            // Footer content
            $html .= '<div class="footer">';
            $html .= '<strong>Growth Connect</strong>';
            $html .= 'Created by: ' . $userName . '<br>';
            $html .= 'Created on: ' . Carbon::now()->format('d F Y');
            $html .= '</div>';
    
            // End PDF content
            $html .= '</body></html>';
    
            // Load HTML content into Dompdf
            $dompdf->loadHtml($html);
    
            // Set paper size and orientation
            $dompdf->setPaper('A4', 'portrait');
    
            // Render PDF
            $dompdf->render();
    
            // Save PDF to storage
            $pdfContent = $dompdf->output();
            $fileName = 'milestones_' . $childID . '_' . now()->format('d-m-Y_H-i-s') . '.pdf';
            $filePath = 'reports/' . $fileName;
            Storage::disk('spaces')->put($filePath, $pdfContent);
    
            return $filePath; // Return the file path of the saved PDF for later access
    }
    
    
    
}
