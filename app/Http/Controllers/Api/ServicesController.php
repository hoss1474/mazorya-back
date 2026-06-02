<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class ServicesController extends Controller
{
    public function index(Request $request)
    {
        // دریافت زبان (پیش‌فرض سیستم اگر ست نشده بود)
        $locale = $request->get('lang', App::getLocale());

        $services = Service::with(['translations' => function ($q) use ($locale) {
            $q->where('locale', $locale);
        }])
            ->where('is_active', true)
            ->latest()
            ->get()
            ->map(fn ($service) => $this->formatService($service, $locale));

        return response()->json([
            'status' => true,
            'data' => $services,
        ]);
    }

    /**
     * تغییر از ID به Lang و Slug برای سئو
     */
    public function show(Request $request, $lang, $slug)
    {
        $service = Service::whereHas('translations', function ($q) use ($lang, $slug) {
            $q->where('slug', $slug)->where('locale', $lang);
        })
            ->with(['translations' => function ($q) use ($lang) {
                $q->where('locale', $lang);
            }])
            ->where('is_active', true)
            ->first();

        if (!$service) {
            return response()->json([
                'status' => false,
                'message' => 'Service not found for this language and slug',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $this->formatService($service, $lang),
        ]);
    }

    /* ================= Formatter ================= */
    protected function formatService(Service $service, $locale): array
    {
        // گرفتن اولین ترجمه (که به خاطر Eager Loading در متد بالا، همان زبان مورد نظر است)
        $t = $service->translations->first();

        // Fallback: اگر ترجمه برای آن زبان نبود، انگلیسی را بگیرد
        if (!$t) {
            $t = $service->translations()->where('locale', 'en')->first();
        }

        return [
            'id' => $service->id,
            'slug' => $t->slug ?? null, // اضافه شده برای ساخت لینک در فرانت
            'lang' => $t->locale ?? $locale,
            'name' => $t->name ?? null,
            'description' => $t->description ?? null,

            'sections' => [
                $this->section($t->title_1 ?? null, $t->description_1 ?? null),
                $this->section($t->title_2 ?? null, $t->description_2 ?? null),
                $this->section($t->title_3 ?? null, $t->description_3 ?? null),
                $this->section($t->title_4 ?? null, $t->description_4 ?? null),
            ],

            'images' => [
                'image_1' => $service->image_url,
                'image_2' => $service->image2_url,
            ],

            'is_active' => (bool)$service->is_active,
            'created_at' => $service->created_at->format('Y-m-d H:i:s'),
        ];
    }

    protected function section($title, $description): array
    {
        return [
            'title' => $title,
            'description' => $description,
        ];
    }
}
