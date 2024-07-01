<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Daycare extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /**
  * The attributes that are mass assignable.
  *
  * @var array
  */

    protected $table = 'daycare'; 
    protected $primaryKey = 'DaycareID'; 

    protected $fillable = [
        'DaycareName',
        'DaycareKhmerName',
        'DaycareContact',
        'DaycareRepresentative',
        'DaycareProofOfIdentity',
        'DaycareImage',
        'DaycareStreetNumber',
        'DaycareVillage',
        'DaycareSangkat',
        'DaycareKhan',
        'DaycareCity',
        'AccountStatusID',
        'OrganizationID',

    
    ];

 /**
  * The attributes that should be hidden for serialization.
  *
  * @var array
  */
    protected $hidden = [
        'DaycarePassword',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
    *
    * @var array
    */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime:d-m-Y', 
        'updated_at' => 'datetime:d-m-Y',
    ];


    // Define a custom accessor for formatted month
    public function getCreatedAtMonthAttribute()
    {
        return $this->created_at->translatedFormat('d F Y'); // Format month as textual (e.g., 10 June 2024)
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'DaycareID', 'DaycareID');
    }

    public function parents()
    {
        return $this->hasMany(Parents::class, 'DaycareID');
    }

    public function children()
    {
        return $this->hasManyThrough(Child::class, Parent::class, 'DaycareID', 'ParentID', 'DaycareID', 'ParentID');
    }
    
    public function staff()
    {
        return $this->hasMany(Staff::class, 'DaycareID', 'DaycareID');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'OrganizationID', 'OrganizationID');
    }

    public function milestones(): HasManyThrough
    {
        return $this->hasManyThrough(
            Milestone::class,
            Child::class,
            'DaycareID', // Foreign key on the Child table
            'ChildID',   // Foreign key on the Milestone table
            'DaycareID', // Local key on the Daycare table
            'ChildID'    // Local key on the Child table
        );
    }
}
