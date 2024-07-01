<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles ;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'OrganizationID',
        'DaycareID',
        'ParentID',
        'StaffID',
        'AdminID',
        'AccountStatusID',
        'active_role'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function getFullName()
    {
        return $this->name;
    }

    public function reports()
    {
        return $this->hasMany(Reports::class, 'id', 'id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'OrganizationID', 'OrganizationID');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'AdminID', 'AdminID');
    }

    public function daycare(): BelongsTo
    {
        return $this->belongsTo(Daycare::class, 'DaycareID', 'DaycareID');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'StaffID', 'StaffID');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Parent::class, 'ParentID', 'ParentID');
    }
}
