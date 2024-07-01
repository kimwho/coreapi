<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reports extends Model
{
    use HasFactory;
    protected $table = 'reports'; 
    protected $primaryKey = 'ReportID'; 

    protected $fillable = [
        'UserID',
        'ChildID',
        'ReportPath'
    
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'UserID', 'id');
    }

    // public function staff()
    // {
    //     return $this->belongsTo(Staff::class, 'StaffID', 'StaffID');
    // }

    public function child()
    {
        return $this->belongsTo(Child::class, 'ChildID', 'ChildID');
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'StaffID', 'StaffID');
    }
}
