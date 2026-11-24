<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProjectTranslation extends Model
{
    /*
    |--------------------------------------------------------------------------
    | Mass Assignment
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'project_id',
        'lang',
        'name',
        'slug',
        'description',
        'sections',
        'category_content',
    ];

    /*
    |--------------------------------------------------------------------------
    | Attribute Casting
    |--------------------------------------------------------------------------
    */

    protected $casts = [
        'sections' => 'array',
        'category_content' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function project(): BelongsTo
    {
        return $this->belongsTo(
            Project::class,
            'project_id'
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

            // Preserve an existing or manually entered slug.
            if (filled($translation->slug)) {
                return;
            }

            // Generate a slug from the project name.
            $baseSlug = self::slugify(
                (string) $translation->name
            );

            if ($baseSlug === '') {
                $baseSlug = 'project';
            }

            // Leave enough space for numeric suffixes.
            $baseSlug = Str::substr($baseSlug, 0, 200);

            $slug = $baseSlug;
            $counter = 2;

            // Slugs must be unique within each language.
            while (
            self::query()
                ->where('lang', $translation->lang)
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
    | Normalize Slug
    |--------------------------------------------------------------------------
    |
    | Supports Persian, Arabic, English and other Unicode
    | letters without removing their original characters.
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
