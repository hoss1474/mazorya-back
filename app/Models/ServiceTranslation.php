<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ServiceTranslation extends Model
{
    protected $fillable = [
        'service_id',
        'locale',
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

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    protected static function booted()
    {
        static::saving(function ($model) {
            if ($model->isDirty('name') || empty($model->slug)) {
                // ایجاد اسلاگ اولیه
                $baseSlug = Str::slug($model->name, '-', null);

                // اگر نام فارسی باشد و Str::slug خالی برگرداند
                if (empty($baseSlug)) {
                    $baseSlug = str_replace(' ', '-', mb_strtolower($model->name));
                }

                $slug = $baseSlug;
                $i = 1;

                // اصلاح این بخش: تغییر lang به locale
                while (self::where('slug', $slug)
                    ->where('locale', $model->locale) // اینجا اصلاح شد
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
