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
        'owned_by',
        'parent_id',
        'is_private',
        'password',
        'location',
        'is_user_root_folder',
    ];

    protected $hidden = [
        'password',
        'location',
        'owned_by',
    ];

    protected $appends = [
        'is_from_me',
        'have_password',
        'parent_have_password'
    ];

    public function getIsFromMeAttribute()
    {
        return $this->attributes['owned_by'] === auth('api')->id();
    }

    public function getHavePasswordAttribute()
    {
        return $this->attributes['password'] !== null;
    }

    public function getParentHavePasswordAttribute()
    {
        if($parent = $this->parent()->first())
        {
            return $parent->password !== "";
        }
        return false;
    }

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
        return $this->hasMany(File::class, 'parent_id')->orderBy('filename');
    }

    public function parent()
    {
        return $this->belongsTo(File::class, 'parent_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owned_by', 'id');
    }

    public function permission()
    {
        return $this->hasMany(Permission::class);
    }
}
