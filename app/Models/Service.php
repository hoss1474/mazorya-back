<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class Service extends Model
{
    /*
    |--------------------------------------------------------------------------
    | Fillable
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'image',
        'image2',
        'is_active',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function translations(): HasMany
    {
        return $this->hasMany(
            ServiceTranslation::class,
            'service_id'
        );
    }

    public function faTranslation(): HasOne
    {
        return $this->hasOne(
            ServiceTranslation::class,
            'service_id'
        )->where('locale', 'fa');
    }

    /*
    |--------------------------------------------------------------------------
    | Image Accessors
    |--------------------------------------------------------------------------
    */

    public function getImageUrlAttribute(): ?string
    {
        return $this->mediaUrl($this->image);
    }

    public function getImage2UrlAttribute(): ?string
    {
        return $this->mediaUrl($this->image2);
    }

    /*
    |--------------------------------------------------------------------------
    | Media URL Helper
    |--------------------------------------------------------------------------
    */

    private function mediaUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        // Preserve already absolute URLs.
        if (preg_match('~^https?://~i', $path)) {
            return $path;
        }

        // Use the same disk as Filament FileUpload.
        $fileUrl = Storage::disk('api_public')
            ->url($path);

        return url($fileUrl);
    }
}
