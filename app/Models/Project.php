<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'name',
        'image',
        'image2',
        'url',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
    public function translations()
    {
        return $this->hasMany(ProjectTranslation::class);
    }

    public function translation($lang = null)
    {
        $lang = $lang ?? app()->getLocale();
        return $this->translations
            ->where('lang', $lang)
            ->first();
    }

    // Accessor برای URL کامل عکس اصلی
    public function getImageUrlAttribute()
    {
        return $this->image ? url(Storage::url($this->image)) : null;
    }

    // Accessor برای URL کامل عکس دوم
    public function getImage2UrlAttribute()
    {
        return $this->image2 ? url(Storage::url($this->image2)) : null;
    }
}
