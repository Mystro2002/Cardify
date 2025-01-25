<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobTitle extends Model
{
    use HasFactory , SoftDeletes;
    protected $guarded = ['id'];

    public function adminJobTitles()
    {
        return $this->hasMany(AdminJob::class);
    }

    public function details()
    {
        return $this->hasMany(JobTitleDetail::class);
    }
}
