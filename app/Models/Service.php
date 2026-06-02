<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Service extends Model
{
    protected $fillable = [
        'image',
        'image2',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getImageUrlAttribute()
    {
        return $this->image ? url(Storage::url($this->image)) : null;
    }

    public function getImage2UrlAttribute()
    {
        return $this->image2 ? url(Storage::url($this->image2)) : null;
    }

    public function translations()
    {
        return $this->hasMany(ServiceTranslation::class);
    }
    public function faTranslation()
    {
        return $this->hasOne(ServiceTranslation::class)
            ->where('locale', 'fa');
    }
}
