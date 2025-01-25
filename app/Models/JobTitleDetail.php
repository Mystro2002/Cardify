<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobTitleDetail extends Model
{
    use HasFactory , SoftDeletes;
    protected $guarded = ['id'];

    public function language()
    {
        return $this->belongsTo(Language::class);
    }
}
