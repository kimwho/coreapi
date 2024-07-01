<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;



use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class Admin extends Authenticatable
{
    use HasApiTokens, Notifiable;

       /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

     protected $table = 'admin'; 
     protected $primaryKey = 'AdminID'; 

    protected $fillable = [
        'AdminName',
        'AdminImage',
        'AdminContact',
        'AccountStatusID',
        'SexID',
    
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [

    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}
