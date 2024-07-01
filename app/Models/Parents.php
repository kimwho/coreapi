<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne; //
class Parents extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /**
  * The attributes that are mass assignable.
  *
  * @var array
  */

    protected $table = 'parent'; 
    protected $primaryKey = 'ParentID'; 

    protected $fillable = [
        'ParentFirstName',
        'ParentLastName',
        'ParentKhmerFirstName',
        'ParentKhmerLastName',
        'ParentDOB',
        'ParentContact',
        'ParentImage',
        'AccountStatusID',
        'SexID',
        'ParentIdentityNumber',
        'ParentStreetNumber',
        'ParentVillage',
        'ParentSangkat',
        'ParentKhan',
        'ParentCity',
        'DaycareID',
    
    ];



 /**
  * The attributes that should be hidden for serialization.
  *
  * @var array
  */
    protected $hidden = [
        'ParentPassword',
        'remember_token',
    ];

 /**
  * The attributes that should be cast.
  *
  * @var array
  */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function routeNotificationForMail($notification)
    {
        // Return the email address where notifications should be sent.
        return $this->ParentEmail;
    }

    public function parent()
    {
        return $this->belongsTo(\App\Models\Parents::class, 'ParentID');
    }
    
    public function daycare()
    {
        return $this->belongsTo(Daycare::class, 'DaycareID');
    }

    public function children()
    {
        return $this->hasMany(Child::class, 'ParentID');
    }
    
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'ParentID', 'ParentID');
    }
    
    // public function users(): HasMany
    // {
    //     return $this->hasMany(User::class, 'ParentID', 'ParentID');
    // }

}