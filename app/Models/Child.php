<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Child extends Model
{
    use HasFactory;
    protected $table = 'child'; 
    protected $primaryKey = 'ChildID'; 

    protected $fillable = [
        'ChildFirstName',
        'ChildLastName',
        'ChildKhmerFirstName',
        'ChildKhmerLastName',
        'ChildDOB',
        'ChildImage',
        'AccountStatusID',
        'SexID',
        'ParentID',
        'childtypeID',
        'childtype_changed_at'
    
    ];
    

    public static function boot()
    {
        parent::boot();

        static::updating(function ($child) {
            if ($child->isDirty('childtypeID')) {
                if ($child->getOriginal('childtypeID') == 1 && $child->childtypeID == 2) {
                    $child->childtype_changed_at = now();
                } elseif ($child->childtypeID == 1) {
                    $child->childtype_changed_at = null;
                }
            }
        });
    }

    public function parent()
    {
        return $this->belongsTo(Parents::class, 'ParentID');
    }
 
    public function reports()
    {
        return $this->hasMany(Reports::class, 'ChildID', 'ChildID');
    }

}
