<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Language extends Model
{
    use HasFactory, SoftDeletes;
 
    
    public function deleteWithRelations()
    {
        // Soft delete related books
        $this->jobTitleDetails()->delete();

        // Soft delete the language itself
        return $this->delete();
    }


    public function contentDetails()
    {
        return $this->hasMany(ContentDetail::class, 'language_id');
    }
    public function jobTitleDetails()
    {
        return $this->hasMany(JobTitleDetail::class , 'language_id');
    }
}
