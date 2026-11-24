<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Project extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | Mass Assignment
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'category',
        'client',
        'year',
        'url',
        'image',
        'image2',
        'gallery',
        'technologies',
        'category_data',
        'is_active',
        'is_marked',
    ];

    /*
    |--------------------------------------------------------------------------
    | Attribute Casting
    |--------------------------------------------------------------------------
    */

    protected $casts = [
        'year' => 'integer',

        'gallery' => 'array',
        'technologies' => 'array',
        'category_data' => 'array',

        'is_active' => 'boolean',
        'is_marked' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function translations(): HasMany
    {
        return $this->hasMany(
            ProjectTranslation::class,
            'project_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Get Translation By Language
    |--------------------------------------------------------------------------
    */

    public function translation(
        ?string $lang = null
    ): ?ProjectTranslation {
        $lang ??= app()->getLocale();

        // Use eager-loaded translations when available.
        if ($this->relationLoaded('translations')) {
            return $this->translations
                ->firstWhere('lang', $lang);
        }

        return $this->translations()
            ->where('lang', $lang)
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Main Image URL
    |--------------------------------------------------------------------------
    */

    public function getImageUrlAttribute(): ?string
    {
        return $this->publicMediaUrl($this->image);
    }

    /*
    |--------------------------------------------------------------------------
    | Second Image URL
    |--------------------------------------------------------------------------
    */

    public function getImage2UrlAttribute(): ?string
    {
        return $this->publicMediaUrl($this->image2);
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Public Media URL
    |--------------------------------------------------------------------------
    */

    private function publicMediaUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        $fileUrl = Storage::disk('api_public')->url($path);

        return url($fileUrl);
    }
}
