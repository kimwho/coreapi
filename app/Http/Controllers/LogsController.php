<?php

namespace App\Http\Controllers;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Organization;
use App\Models\Daycare;
use App\Models\Parents;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;

class LogsController extends Controller
{
  

    public function getActivityLogsByDaycareID(Request $request, $daycareID)
    {
        try {
            // Fetch the activity logs for users with the specified DaycareID
            $logs = ActivityLog::join('users', 'activity_logs.user_id', '=', 'users.id')
                ->join('daycare', 'users.DaycareID', '=', 'daycare.DaycareID')
                ->where('users.DaycareID', $daycareID)
                ->select(
                    'activity_logs.id as log_id',
                    'activity_logs.user_id',
                    'activity_logs.user_name',
                    'activity_logs.activity',
                    'users.DaycareID',
                    'daycare.DaycareName',
                    // Format the 'created_at' as Day-Month-Year Hour:Minute AM/PM
                    DB::raw("DATE_FORMAT(activity_logs.created_at, '%d-%b-%Y %h:%i %p') as formatted_created_at"),
                    'activity_logs.created_at'
                )
                ->orderBy('activity_logs.created_at', 'desc')
                ->get();
    
            // Return a success response with the logs
            return response()->json(['success' => true, 'logs' => $logs], 200);
        } catch (\Exception $e) {
            // Return an error response if something goes wrong
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    

    // public function getLogsByOrganization($organizationId)
    // {
    //     $logs = ActivityLog::whereHas('user', function($query) use ($organizationId) {
    //         // Check if the user belongs to the organization or daycare associated with $organizationId
    //         $query->where('OrganizationID', $organizationId)
    //               ->orWhere(function($query) use ($organizationId) {
    //                   // Subquery to check if the daycare associated with the user belongs to the organization
    //                   $query->whereHas('daycare', function($query) use ($organizationId) {
    //                       $query->where('OrganizationID', $organizationId);
    //                   })->whereNotNull('DaycareID');
    //               });
    //     })->with(['user', 'user.daycare'])->get();
    
    //     $response = $logs->map(function($log) {
    //         return [
    //             'user_name' => $log->user->name,
    //             'activity' => $log->activity,
    //             'date' => $log->created_at->toDateTimeString(),
    //             'daycare_name' => optional($log->user->daycare)->DaycareName, // Get daycare name if available
    //         ];
    //     });
    
    //     return response()->json($response);
    // }

    public function getLogsByOrganization($organizationId)
{
    $logs = ActivityLog::whereHas('user', function($query) use ($organizationId) {
        // Check if the user belongs to the organization or daycare associated with $organizationId
        $query->where('OrganizationID', $organizationId)
              ->orWhere(function($query) use ($organizationId) {
                  // Subquery to check if the daycare associated with the user belongs to the organization
                  $query->whereHas('daycare', function($query) use ($organizationId) {
                      $query->where('OrganizationID', $organizationId);
                  })->whereNotNull('DaycareID');
              });
    })->with(['user', 'user.daycare'])->get();

    $response = $logs->map(function($log) {
        return [
            'user_name' => $log->user->name,
            'activity' => $log->activity,
            'date' => $log->created_at->format('d-M-Y h:i A'), // Format to dd-MMM-yyyy hh:mm AM/PM
            'daycare_name' => optional($log->user->daycare)->DaycareName, // Get daycare name if available
        ];
    });

    return response()->json($response);
}
    
}
