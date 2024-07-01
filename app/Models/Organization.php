<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Organization extends Authenticatable 
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'organization';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'OrganizationID';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'OrganizationName',
        'OrganizationKhmerName',
        'OrganizationContact',
        'OrganizationRepresentative',
        'OrganizationProofOfIdentity',
        'OrganizationImage',
        'OrganizationStreetNumber',
        'OrganizationVillage',
        'OrganizationSangkat',
        'OrganizationKhan',
        'OrganizationCity',
        'AccountStatusID',

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'OrganizationPassword',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'OrganizationID', 'OrganizationID');
    }

    public function daycares()
    {
        return $this->hasMany(Daycare::class, 'OrganizationID', 'OrganizationID');
    }
}
