<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrganizationAuthController;
use App\Http\Controllers\DaycareAuthController;
use App\Http\Controllers\ParentAuthController;
use App\Http\Controllers\StaffAuthController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\RoleandPermissionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChildController;
use App\Http\Controllers\ParentController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\DaycareController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\MilestoneController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\LogsController;

    Route::post('/admin/register', [AdminAuthController::class, 'register']);

    Route::post('/org/register', [OrganizationAuthController::class, 'register']);
    Route::post('/daycare/register', [DaycareAuthController::class, 'register']);
    
    

    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/password/forgot', [AuthController::class, 'forgot']);
    Route::post('/password/reset', [AuthController::class, 'reset']);

    //roles
    Route::post('/roles', [RoleandPermissionController::class, 'createRole']);
    Route::get('/roles', [RoleandPermissionController::class, 'getRoles']);
    Route::get('/roles/{id}', [RoleandPermissionController::class, 'getRole']);
    Route::put('/roles/{id}', [RoleandPermissionController::class, 'updateRole']);
    Route::delete('/roles/{id}', [RoleandPermissionController::class, 'deleteRole']);

    //permissions
    Route::post('/permissions', [RoleandPermissionController::class, 'createPermission']);
    Route::get('/permissions', [RoleandPermissionController::class, 'getPermissions']);
    Route::get('/permissions/{id}', [RoleandPermissionController::class, 'getPermission']);
    Route::put('/permissions/{id}', [RoleandPermissionController::class, 'updatePermission']);
    Route::delete('/permissions/{id}', [RoleandPermissionController::class, 'deletePermission']);

    // User role and permission assignment routes
    Route::post('/assign-permissions/{roleId}', [RoleandPermissionController::class, 'assignPermissionsToRole']);
    Route::post('roles/{roleId}/remove-permission', [RoleandPermissionController::class, 'removePermissionFromRole']);

 
    Route::get('/users/{userId}/roles', [RoleandPermissionController::class, 'getUserRoles']);
    Route::post('/revoke-role', [RoleandPermissionController::class, 'revokeRoleFromUser']);
    Route::get('/users/{userId}/permissions', [RoleandPermissionController::class, 'getUserPermissions']);

    Route::middleware('auth:sanctum')->post('/switch-role', [RoleandPermissionController::class, 'switchRole']);

    Route::get('/organization', [OrganizationController::class, 'allorganizations']);


    // // Protected routes
    Route::middleware(['auth:sanctum'])->group(function () {

        Route::middleware(['role:admin'])->group(function () {
            Route::get('/admin/{id}', [AdminAuthController::class, 'showspecificadmin']);
            Route::get('/child', [ChildController::class, 'index']); //ok
            Route::get('/parent', [ParentController::class, 'allparent']);//ok
            Route::get('/staff', [StaffController::class, 'allstaff']);//ok
            Route::get('/daycare', [DaycareController::class, 'alldaycares']);//ok
            Route::get('/parent/active', [ParentController::class, 'allactiveparent']);//ok
            Route::get('/parent/inactive', [ParentController::class, 'allinactiveparent']);//ok
            Route::get('/staff/active', [StaffController::class, 'allactivestaff']);
            Route::get('/staff/inactive', [StaffController::class, 'allinactivestaff']);
            Route::get('/daycare/active', [DaycareController::class, 'allactivedaycare']);
            Route::get('/daycare/inactive', [DaycareController::class, 'allinactivedaycare']);

        });

        Route::middleware(['role:admin,organization superadmin,organization admin,daycare superadmin,daycare admin,daycare staff,daycare parent'])->group(function () {
            Route::get('/organization/{id}', [OrganizationController::class, 'showspecificorganization']);//super
            Route::get('/daycare/{id}', [DaycareController::class, 'showspecificdaycare']);//super, parent
            Route::get('/parent/{id}', [ParentController::class, 'showspecificparent']);//super, parent 
            Route::get('/staff/{id}', [StaffController::class, 'showspecificstaff']);//super
            Route::get('/child/{id}', [ChildController::class, 'show']);//super, parent
           
            // for dashboard
            Route::get('/parent/by-daycare/{daycareID}', [ParentController::class, 'allParentsByDaycareID']);//ok
            Route::get('/parent/active/by-daycare/{daycareID}', [ParentController::class, 'allActiveParentsByDaycareID']);//ok
            Route::get('/parent/inactive/by-daycare/{daycareID}', [ParentController::class, 'allInactiveParentsByDaycareID']);  //ok     
            Route::get('/staff/by-daycare/{daycareID}', [StaffController::class, 'allstaffByDaycareID']);//ok
            Route::get('/staff/active/by-daycare/{daycareID}', [StaffController::class, 'allActiveStaffsByDaycareID']);//ok
            Route::get('/staff/inactive/by-daycare/{daycareID}', [StaffController::class, 'allInactiveStaffsByDaycareID']);//ok


            Route::get('/parents/{parentID}/children', [ChildController::class, 'showChildrenByParentID']);//super, parent

            Route::get('daycare/{daycareID}/children/active', [ChildController::class, 'getChildrenByDaycareIDAndStatusActive']);
            Route::get('daycare/{daycareID}/children/inactive', [ChildController::class, 'getChildrenByDaycareIDAndStatusInactive']);

            Route::get('/parent/{parentId}/with-children', [ParentController::class, 'getParentWithChildren']);//super, parent
            
            Route::get('/parents-with-children', [ParentController::class, 'listParentsWithChildren']);//super, parent
            Route::get('/milestones/count/{daycareID}', [MilestoneController::class, 'countMilestonesToday']);//super
            Route::get('count-milestones-today-with-likes/{daycareID}', [MilestoneController::class, 'countMilestonesTodayWithLikes']);//super
            Route::get('/milestones/count/week/{daycareID}', [MilestoneController::class, 'countMilestonesForaWeek']);//super
            Route::get('count-milestones-week-with-likes/{daycareID}', [MilestoneController::class, 'countMilestonesForWeekWithLikes']);//super
            Route::get('count-reports/current-date/{daycareID}', [ReportController::class, 'countReportsByCurrentDate']);//super
            Route::get('count-reports/week/{daycareID}', [ReportController::class, 'countReportsForWeek']);//super
            Route::get('daycares/{daycareId}/children', [ChildController::class, 'childwithdaycareid']);  //super
            Route::get('/milestone/child/{ChildID}', [MilestoneController::class, 'getMilestoneWithMediabyChildID']);//super, parent
            Route::get('/milestone/{id}', [MilestoneController::class, 'show']); //super, parent
            Route::get('/reports/{childId}', [ReportController::class, 'viewReport']); //super, parent
            Route::get('/reports/by-parent/{parentId}', [ReportController::class, 'getReportsByParentId']);
            Route::get('/organization/{organizationId}/daycares', [DaycareController::class, 'getDaycareByOrganizationId']);


            Route::get('/count/graduate-student', [ChildController::class, 'countGraduateStudents']);
            Route::get('/count/graduate-student/daycare/{daycareID}', [ChildController::class, 'countChildtypeIDChangesByDaycare']);
            Route::get('/count/graduate-student/organization/{organizationID}', [ChildController::class, 'countChildtypeIDChangesByOrganization']);
        });

        Route::middleware(['role:organization superadmin,organization admin,daycare superadmin,daycare admin'])->group(function () {
            Route::post('/parent/register', [ParentAuthController::class, 'register']);//ok
            Route::post('/staff/register', [StaffAuthController::class, 'register']);//ok
            Route::post('/daycare/{id}', [DaycareController::class, 'updatedaycare']);//ok
        });

        Route::middleware(['role:daycare superadmin,daycare admin,daycare staff'])->group(function () {
            Route::post('/staff/{id}', [StaffController::class, 'updatestaff']);//ok both
        });

        Route::middleware(['role:daycare superadmin,daycare admin,daycare parent'])->group(function () {
            Route::post('/parent/{id}', [ParentController::class, 'updateparent']);// ok both
        });

        Route::middleware(['role:organization superadmin'])->group(function () {
            Route::post('/org/register/admin', [OrganizationAuthController::class, 'registerorganizationadmin']);
            Route::get('organization/{organizationId}/logs', [LogsController::class, 'getLogsByOrganization']);
        });     

        Route::middleware(['role:organization superadmin,organization admin'])->group(function () {
            Route::get('/organizations/alldata/{organizationId}', [OrganizationController::class, 'getalldatafrommyOrg']);
            Route::post('/organization/{id}', [OrganizationController::class, 'updateorganization']);//ok
            Route::put('/daycare/{id}/active', [DaycareController::class, 'activateDaycareAccount']);//okk
            Route::put('/daycare/{id}/inactive', [DaycareController::class, 'deactivateDaycareAccount']);//ok
            // Route::delete('/daycare/{id}', [DaycareController::class, 'deletedaycare']);
            Route::get('/child/by_organization/{organizationId}', [ChildController::class, 'getchildByOrganizationId']);//ok
            Route::get('/children/active/by_organization/{organizationId}', [ChildController::class, 'getActiveChildrenByOrganizationId']);
            Route::get('/children/inactive/by_organization/{organizationId}', [ChildController::class, 'getInactiveChildrenByOrganizationId']);

            Route::get('/parent/by_organization/{organizationId}', [ParentController::class, 'showparentbyorgid']);//ok
            Route::get('/parent/active/by_organization/{organizationId}', [ParentController::class, 'showAllActiveParentByOrgId']);//ok
            Route::get('/parent/inactive/by_organization/{organizationId}', [ParentController::class, 'showAllInactiveParentByOrgId']);//ok

            Route::get('/staff/by_organization/{organizationId}', [StaffController::class, 'getAllStaffByOrganizationId']);//ok
            Route::get('/staff/active/by_organization/{organizationId}', [StaffController::class, 'getAllActiveStaffByOrganizationId']);//ok
            Route::get('/staff/inactive/by_organization/{organizationId}', [StaffController::class, 'getAllInactiveStaffByOrganizationId']);//ok


            Route::get('/daycare/by-organization/{OrganizationID}', [DaycareController::class, 'allDaycaresByOrganizationID']);//ok
            Route::get('/daycare/active/by-organization/{OrganizationID}', [DaycareController::class, 'allActiveDaycaresByOrganizationID']);
            Route::get('/daycare/inactive/by-organization/{OrganizationID}', [DaycareController::class, 'allInActiveDaycaresByOrganizationID']);
            // Route::get('/organization/{organizationId}/daycares', [DaycareController::class, 'getDaycareByOrganizationId']);
            Route::get('/allaccount/daystaffparent/{organizationId}', [OrganizationController::class, 'getDaycareStaffParentByOrganizationId']);
            Route::get('/allaccount/organization/{organizationId}', [OrganizationController::class, 'getUsersByOrganizationId']);
            Route::get('admins/organization/{organizationId}', [OrganizationController::class, 'getAdminsByOrganization']);
        });

        Route::middleware(['role:daycare superadmin'])->group(function () {
            Route::post('/daycare/register/admin', [DaycareAuthController::class, 'registerdaycareadmin']);
            Route::get('/allaccount/daycare/{daycareId}', [DaycareController::class, 'getUsersByDaycareID']);
            Route::get('/daycares/{daycareId}/logs', [LogsController::class, 'getActivityLogsByDaycareID']);
            Route::get('/allmilestones/deleted/daycare/{daycareID}', [MilestoneController::class, 'getDeletedMilestonesByDaycare']);
            Route::get('/milestones/deleted/{id}', [MilestoneController::class, 'showDeletedMilestone']);
        });

        Route::middleware(['role:daycare superadmin,daycare admin'])->group(function () {
            Route::post('/child', [ChildController::class, 'store']);//ok
            Route::post('/child/{id}', [ChildController::class, 'update']);//ok
            Route::put('/staff/{id}/active', [StaffController::class, 'activateStaffAccount']);//ok
            Route::put('/staff/{id}/inactive', [StaffController::class, 'deactivateStaffAccount']);//ok
            Route::put('/parent/{id}/active', [ParentController::class, 'activateParentAccount']);//ok
            Route::put('/parent/{id}/inactive', [ParentController::class, 'deactivateParentAccount']);//ok
            Route::put('/child/{id}/active', [ChildController::class, 'activateChildAccount']);//ok
            Route::put('/child/{id}/inactive', [ChildController::class, 'deactivateChildAccount']);//ok
            // Route::delete('/staff/{id}', [StaffController::class, 'deletestaff']);
            // Route::get('admins/daycare/{daycareId}', [DaycareController::class, 'getAdminsByDaycareId']);

            Route::post('milestones/restore/{id}', [MilestoneController::class, 'restore']);

        });

        Route::middleware(['role:daycare staff'])->group(function () {
            Route::post('/milestone', [MilestoneController::class, 'create']);//ok
            Route::delete('milestones/{id}', [MilestoneController::class, 'delete']);//ok
            Route::post('/reports', [MilestoneController::class, 'createAndGeneratePDF']);//ok
            Route::delete('/reports/{reportId}', [ReportController::class, 'deleteReport'])->name('report.delete');  //ok
        });

        Route::middleware(['role:daycare parent'])->group(function () {
            Route::put('/milestones/{id}/update-likes', [MilestoneController::class, 'updateLikes']);
        });

        Route::middleware(['role:organization superadmin,daycare superadmin'])->group(function () {
            //asign role to user
            Route::post('/assign-roles', [RoleandPermissionController::class, 'assignRolesToUser']);
            //remove role from user
            Route::post('/remove-role', [RoleandPermissionController::class, 'removeRoleFromUser']);
        });
        
    });





   