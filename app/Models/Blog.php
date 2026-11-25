<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Blog extends Model
{
    use HasFactory;

    protected $fillable = [
        'image_1',
        'image_2',
        'published_at',
        'is_active',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(
            BlogTranslation::class,
            'blog_id'
        );
    }

    public function translation(
        ?string $locale = null
    ): ?BlogTranslation {
        $locale ??= app()->getLocale();

        if ($this->relationLoaded('translations')) {
            return $this->translations
                ->firstWhere('locale', $locale);
        }

        return $this->translations()
            ->where('locale', $locale)
            ->first();
    }

    public function getImage1UrlAttribute(): ?string
    {
        return $this->mediaUrl($this->image_1);
    }

    public function getImage2UrlAttribute(): ?string
    {
        return $this->mediaUrl($this->image_2);
    }

    private function mediaUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (preg_match('~^https?://~i', $path)) {
            return $path;
        }

        $fileUrl = Storage::disk('api_public')
            ->url($path);

        return preg_match('~^https?://~i', $fileUrl)
            ? $fileUrl
            : url($fileUrl);
    }
}
