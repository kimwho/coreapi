<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Staff extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'staff';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'StaffID';

    /**
     * The attributes that are mass assignable.
    *
    * @var array
    */


 protected $fillable = [
     'StaffFirstName',
     'StaffLastName',
     'StaffKhmerFirstName',
     'StaffKhmerLastName',
     'StaffDOB',
     'StaffContact',
     'StaffIdentityNumber',
     'StartedWorkDate',
     'StaffImage',
     'AccountStatusID',
     'SexID',
     'DaycareID',

 ];

 /**
  * The attributes that should be hidden for serialization.
  *
  * @var array
  */
 protected $hidden = [
     'StaffPassword',
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

 public function users(): HasMany
 {
     return $this->hasMany(User::class, 'StaffID', 'StaffID');
 }

 public function daycare()
 {
     return $this->belongsTo(Daycare::class, 'DaycareID', 'DaycareID');
 }

 public function reports()
 {
     return $this->hasMany(Reports::class, 'StaffID', 'StaffID');
 }
}
