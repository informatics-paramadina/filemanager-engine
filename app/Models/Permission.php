<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_id',
        'granted_to',
        'action',
        'have_expired',
        'expired_on',
    ];
}
