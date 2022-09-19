<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class File extends Model
{
    use HasFactory;

    protected $fillable = [
        'filename',
        'description',
        'extension',
        'mime_type',
        'size',
        'owner',
        'parent_id',
        'is_private',
        'password',
        'location',
    ];

    protected $hidden = [
        'password',
        'location',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = Str::uuid()->toString();
            }
        });
    }


    public function getIncrementing()
    {
        return false;
    }

    public function getKeyType()
    {
        return 'string';
    }

    public function children()
    {
        return $this->hasMany(File::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(File::class, 'parent_id');
    }
}
