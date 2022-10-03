<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AllowedEmail extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'tag',
        'group',
        'is_allowed'
    ];
}
