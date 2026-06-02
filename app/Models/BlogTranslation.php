<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BlogTranslation extends Model
{
    protected $fillable = [
        'blog_id',
        'locale',
        'title', // فیلد اصلی برای ساخت اسلاگ
        'description',
        'title_1', 'description_1',
        'title_2', 'description_2',
        'title_3', 'description_3',
        'title_4', 'description_4',
        'title_5', 'description_5',
        'slug',
    ];

    public function blog()
    {
        return $this->belongsTo(Blog::class);
    }

    protected static function booted()
    {
        static::saving(function ($model) {
            // تغییر مهم: استفاده از title به جای name
            if ($model->isDirty('title') || empty($model->slug)) {

                // ساخت اسلاگ اولیه از روی title
                $baseSlug = Str::slug($model->title, '-', null);

                // هندل کردن کلمات فارسی/عربی اگر Str::slug خالی بود
                if (empty($baseSlug)) {
                    $baseSlug = str_replace(' ', '-', mb_strtolower($model->title));
                }

                $slug = $baseSlug;
                $i = 1;

                // چک کردن تکراری نبودن اسلاگ در این زبان (locale)
                while (self::where('slug', $slug)
                    ->where('locale', $model->locale)
                    ->where('id', '!=', $model->id)
                    ->exists()) {
                    $slug = $baseSlug . '-' . $i;
                    $i++;
                }
                $model->slug = $slug;
            }
        });
    }
}
