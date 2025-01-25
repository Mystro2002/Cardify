<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdminJob extends Model
{
    use HasFactory, SoftDeletes;

    // Define the relationships
    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function jobTitle()
    {
        return $this->belongsTo(JobTitle::class);
    }
}
