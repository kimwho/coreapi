<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Milestone extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'milestone'; 
    protected $primaryKey = 'MilestoneID'; 

    protected $fillable = [
        'Description',
        'Likes',
        'StaffID',
        'ChildID',
        'UserID',
        'deleted_by'
    
    ];

    public function images()
    {
        return $this->hasMany(Images::class, 'MilestoneID', 'MilestoneID');
    }

    public function videos()
    {
        return $this->hasMany(Videos::class, 'MilestoneID', 'MilestoneID');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'UserID', 'id');
    }

    public function child()
    {
        return $this->belongsTo(Child::class, 'ChildID', 'ChildID');
    }

    public static function getDeletedMilestonesByDaycareID($daycareID)
    {
        return self::onlyTrashed()
                    ->whereHas('child.parent', function ($query) use ($daycareID) {
                        $query->where('DaycareID', $daycareID);
                    })
                    ->with(['images', 'videos', 'user', 'child'])
                    ->get();
    }

    public function deletedByUser()
{
    return $this->belongsTo(User::class, 'deleted_by', 'id');
}
}
