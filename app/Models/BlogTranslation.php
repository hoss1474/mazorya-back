<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BlogTranslation extends Model
{
    protected $fillable = [
        'blog_id',
        'locale',
        'title',
        'slug',
        'description',
        'excerpt',
        'category_name',
        'quote',
        'sections',
        'takeaways',
    ];

    protected $casts = [
        'sections' => 'array',
        'takeaways' => 'array',
    ];

    public function blog(): BelongsTo
    {
        return $this->belongsTo(
            Blog::class,
            'blog_id'
        );
    }

    protected static function booted(): void
    {
        static::saving(function (self $translation): void {

            // Preserve an existing or manually entered slug.
            if (filled($translation->slug)) {
                return;
            }

            $baseSlug = self::slugify(
                (string) $translation->title
            );

            if ($baseSlug === '') {
                $baseSlug = 'blog';
            }

            $baseSlug = Str::substr($baseSlug, 0, 180);

            $slug = $baseSlug;
            $counter = 2;

            // Check uniqueness within the same language.
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
