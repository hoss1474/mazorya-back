<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProjectTranslation extends Model
{
    protected $fillable = [
        'project_id',
        'lang',
        'name',
        'description',
        'title_1',
        'description_1',
        'title_2',
        'description_2',
        'title_3',
        'description_3',
        'title_4',
        'description_4',
        'slug',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }


    protected static function booted()
    {
        static::saving(function ($model) {
            // اگر نام تغییر کرده بود یا اسلاگ خالی بود
            if ($model->isDirty('name') || empty($model->slug)) {
                // پارامتر دوم '' اجازه میده حروف فارسی/عربی هم در اسلاگ بمونن
                $baseSlug = Str::slug($model->name, '-', null);

                // اگر Str::slug برای فارسی خالی برگردوند (در نسخه‌های قدیمی)
                // از این جایگزین استفاده کن: str_replace(' ', '-', $model->name)

                $slug = $baseSlug;
                $i = 1;

                while (self::where('slug', $slug)
                    ->where('lang', $model->lang)
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

