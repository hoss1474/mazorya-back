<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class BlogController extends Controller
{
    /**
     * لیست همه بلاگ‌های فعال بر اساس زبان
     */
    public function index(Request $request)
    {
        // دریافت زبان (اگر در ورودی نبود، زبان پیش‌فرض)
        $locale = $request->get('locale', App::getLocale());

        $blogs = Blog::with(['translations' => function ($query) use ($locale) {
            // اصلاح شد: تغییر lang به locale
            $query->where('locale', $locale);
        }])
            ->where('is_active', true)
            ->latest()
            ->get()
            ->map(fn($blog) => $this->formatBlog($blog, $locale));

        return response()->json([
            'status' => true,
            'data' => $blogs,
        ]);
    }

    /**
     * نمایش جزئیات یک بلاگ بر اساس زبان و اسلاگ (SEO Friendly)
     */
    public function show(Request $request, $lang, $slug)
    {
        // پیدا کردن بلاگ از طریق جدول ترجمه
        $blog = Blog::whereHas('translations', function ($query) use ($lang, $slug) {
            // اصلاح شد: تغییر lang به locale
            $query->where('slug', $slug)->where('locale', $lang);
        })
            ->with(['translations' => function ($query) use ($lang) {
                // اصلاح شد: تغییر lang به locale
                $query->where('locale', $lang);
            }])
            ->where('is_active', true)
            ->first();

        if (!$blog) {
            return response()->json([
                'status' => false,
                'message' => 'Blog not found for this language and slug',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $this->formatBlog($blog, $lang),
        ]);
    }

    /**
     * قالب‌بندی داده‌ها برای خروجی API
     */
    private function formatBlog(Blog $blog, string $locale): array
    {
        // اصلاح شد: تغییر جستجو از lang به locale
        $translation = $blog->translations->firstWhere('locale', $locale);

        return [
            'id' => $blog->id,
            'slug' => $translation?->slug,
            'lang' => $locale,
            'title' => $translation?->title,
            'description' => $translation?->description,
            'sections' => [
                ['title' => $translation?->title_1, 'description' => $translation?->description_1],
                ['title' => $translation?->title_2, 'description' => $translation?->description_2],
                ['title' => $translation?->title_3, 'description' => $translation?->description_3],
                ['title' => $translation?->title_4, 'description' => $translation?->description_4],
                ['title' => $translation?->title_5, 'description' => $translation?->description_5],
            ],
            'images' => [
                'image_1' => $blog->image1_url,
                'image_2' => $blog->image2_url,
                'image_3' => $blog->image3_url,
            ],
            'is_active' => (bool) $blog->is_active,
            'created_at' => $blog->created_at ? $blog->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $blog->updated_at ? $blog->updated_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
