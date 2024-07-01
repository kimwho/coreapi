<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Images extends Model
{
    use HasFactory, SoftDeletes;
    protected $primaryKey = 'ImageID';
    protected $fillable = [
        'MilestoneID',
        'ImagePath',
        'deleted_by'
    ];

    public function milestone()
    {
        return $this->belongsTo(Milestone::class, 'MilestoneID', 'MilestoneID');
    }
}
