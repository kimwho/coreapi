<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reports;
use App\Models\Child;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Storage;
class ReportController extends Controller
{

    public function viewReport($childId)
    {
        // Find all reports based on ChildID
        $reports = Reports::where('ChildID', $childId)->get();
    
        // If reports exist, return the data
        if ($reports->isNotEmpty()) {
            $responseData = [];
            foreach ($reports as $report) {
                $child = $report->child()->first(); // Get the related child
                $user = $report->user()->first();   // Get the related user
    
                // Format created_at timestamp to dd-mm-yyyy
                $createdAtFormatted = date('d F Y', strtotime($report->created_at));
                $responseData[] = [
                    'ReportID' => $report->ReportID,
                    'ChildFirstName' => $child->ChildFirstName,
                    'ChildLastName' => $child->ChildLastName,
                    'ReportPath' => $report->ReportPath,
                    'UserName' => $user->name,        // Include user's name
                    'created_at' => $createdAtFormatted
                ];
            }
            return response()->json(['reports' => $responseData], 200);
        } else {
            // If no reports found, return a 404 error
            return response()->json(['error' => 'Reports not found for this child.'], 404);
        }
    }
    
    public function deleteReport(Request $request, $reportId){
        try {
            // Find the report by its ID
            $report = Reports::findOrFail($reportId);
            
            // Retrieve the child associated with the report
            $child = $report->child;

            // Delete the report file from storage
            $filePath = $report->ReportPath;
            Storage::disk('spaces')->delete($filePath);
            
            
            $user = $request->user();
    
            // Log the activity
            ActivityLog::create([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'activity' => "{$user->name} deleted a report of {$child->ChildFirstName} {$child->ChildLastName}",
            ]);

            // Delete the report record from the database
            $report->delete();
            
            // Return a success message
            return response()->json(['message' => 'Report deleted successfully'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // If the report is not found, return a message
            return response()->json(['error' => 'Report not found'], 404);
        } catch (\Exception $e) {
            // If an exception occurs, handle it
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    public function countReportsByCurrentDate(Request $request, $daycareID){
        // Get the current date
        $currentDate = now()->toDateString();
        
        // Count the reports for the daycare for the current date
        $reportCount = Reports::whereHas('user', function ($query) use ($daycareID) {
                $query->where('DaycareID', $daycareID);
            })
            ->whereDate('created_at', $currentDate)
            ->count();
    
        return response()->json(['report_count' => $reportCount]);
    }

    public function countReportsForWeek(Request $request, $daycareID){
        // Get the start and end dates for the week
        $startDate = now()->startOfWeek()->toDateString();
        $endDate = now()->endOfWeek()->toDateString();
    
        // Count the reports for the daycare for the current week
        $reportCount = Reports::whereHas('user', function ($query) use ($daycareID) {
                $query->where('DaycareID', $daycareID);
            })
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->count();
    
        return response()->json(['report_count' => $reportCount]);
    }
    

    public function getReportsByParentId($parentId)
    {
        // Find all children based on ParentID
        $children = Child::where('ParentID', $parentId)->get();
    
        if ($children->isNotEmpty()) {
            $responseData = [];
    
            foreach ($children as $child) {
                $reports = Reports::where('ChildID', $child->ChildID)->get();
    
                foreach ($reports as $report) {
                    $user = $report->user()->first(); // Get the related user
    
                    // Format created_at timestamp to dd-mm-yyyy
                    $createdAtFormatted = date('d F Y', strtotime($report->created_at));
                    $responseData[] = [
                        'ReportID' => $report->ReportID,
                        'ChildFirstName' => $child->ChildFirstName,
                        'ChildLastName' => $child->ChildLastName,
                        'ReportPath' => $report->ReportPath,
                        'GeneratedBy' => $user->name, // Include user's name
                        'created_at' => $createdAtFormatted
                    ];
                }
            }
    
            return response()->json(['reports' => $responseData], 200);
        } else {
            // If no children found for the given ParentID, return a 404 error
            return response()->json(['error' => 'No children found for this parent.'], 404);
        }
    }

}
