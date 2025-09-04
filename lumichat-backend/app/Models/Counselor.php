<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Counselor extends Model
{
    protected $table = 'tbl_counselors';   // <-- important
    protected $fillable = ['name','email','phone','is_active'];

    public function availabilities(): HasMany {
        return $this->hasMany(CounselorAvailability::class, 'counselor_id');
    }
}

