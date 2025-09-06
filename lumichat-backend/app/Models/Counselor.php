<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Counselor extends Model
{
    protected $table = 'tbl_counselors';

    protected $fillable = [
        'name', 'email', 'phone', 'is_active',
    ];

    // handy scope
    public function scopeActive($q) { return $q->where('is_active', true); }
}
