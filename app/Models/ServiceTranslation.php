<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ServiceTranslation extends Model
{
    /*
    |--------------------------------------------------------------------------
    | Fillable
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'service_id',
        'locale',
        'name',
        'slug',
        'description',
        'short_description',
        'approach_description',
        'deliverables',
        'process',
        'sections',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected $casts = [
        'deliverables' => 'array',
        'process' => 'array',
        'sections' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function service(): BelongsTo
    {
        return $this->belongsTo(
            Service::class,
            'service_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Automatic Slug Generation
    |--------------------------------------------------------------------------
    */

    protected static function booted(): void
    {
        static::saving(function (self $translation): void {

            // Preserve manually entered slugs.
            if (filled($translation->slug)) {
                return;
            }

            // Generate slug from service name.
            $baseSlug = self::slugify(
                (string) $translation->name
            );

            if ($baseSlug === '') {
                $baseSlug = 'service';
            }

            // Reserve space for numeric suffixes.
            $baseSlug = Str::substr($baseSlug, 0, 180);

            $slug = $baseSlug;
            $counter = 2;

            // Check uniqueness inside the same language.
            while (
            self::query()
                ->where('locale', $translation->locale)
                ->where('slug', $slug)
                ->when(
                    $translation->exists,
                    fn ($query) => $query->where(
                        'id',
                        '!=',
                        $translation->getKey()
                    )
                )
                ->exists()
            ) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            $translation->slug = $slug;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Slug Normalization
    |--------------------------------------------------------------------------
    |
    | Supports Unicode languages including Persian,
    | Arabic, English, German, French and Spanish.
    |
    */

    public static function slugify(string $value): string
    {
        $value = Str::lower(trim($value));

        $slug = preg_replace(
            '/[^\p{L}\p{M}\p{N}]+/u',
            '-',
            $value
        );

        return trim($slug ?? '', '-');
    }
}
