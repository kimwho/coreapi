<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Videos extends Model
{
    use HasFactory, SoftDeletes;
    protected $primaryKey = 'VideoID';
    protected $fillable = [
        'MilestoneID',
        'VideoPath',
        'deleted_by'
    ];

    public function milestone()
    {
        return $this->belongsTo(Milestone::class, 'MilestoneID', 'MilestoneID');
    }
}
