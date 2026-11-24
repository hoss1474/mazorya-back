<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServicesController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Supported Languages
    |--------------------------------------------------------------------------
    */

    private const LANGUAGES = [
        'fa', 'en', 'de', 'fr', 'es', 'ar',
    ];

    /*
    |--------------------------------------------------------------------------
    | GET /api/Services
    | GET /api/Services?lang=en
    |--------------------------------------------------------------------------
    */

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lang' => [
                'sometimes',
                'required',
                'string',
                'in:fa,en,de,fr,es,ar',
            ],
        ]);

        $locale = $validated['lang'] ?? app()->getLocale();

        $services = Service::query()
            ->with([
                'translations' => fn ($query) =>
                $query->whereIn(
                    'locale',
                    array_unique([$locale, 'en'])
                ),
            ])
            ->where('is_active', true)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(
                fn (Service $service): array =>
                $this->formatService($service, $locale)
            )
            ->values();

        return response()->json([
            'status' => true,
            'data' => $services,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/Services/{lang}/{slug}
    | GET /api/Services/en/website-design
    |--------------------------------------------------------------------------
    */

    public function show(
        string $lang,
        string $slug
    ): JsonResponse {
        if (!in_array($lang, self::LANGUAGES, true)) {
            return $this->serviceNotFound();
        }

        $service = Service::query()
            ->where('is_active', true)
            ->whereHas('translations', function ($query) use (
                $lang,
                $slug
            ) {
                $query
                    ->where('locale', $lang)
                    ->where('slug', $slug);
            })
            ->with([
                'translations' => fn ($query) =>
                $query->whereIn(
                    'locale',
                    array_unique([$lang, 'en'])
                ),
            ])
            ->first();

        if (!$service) {
            return $this->serviceNotFound();
        }

        return response()->json([
            'status' => true,
            'data' => $this->formatService($service, $lang),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Format Service Response
    |--------------------------------------------------------------------------
    */

    protected function formatService(
        Service $service,
        string $locale
    ): array {
        // Requested language
        $translation = $service->translations
            ->firstWhere('locale', $locale);

        // English fallback
        if (!$translation) {
            $translation = $service->translations
                ->firstWhere('locale', 'en');
        }

        // Content sections (1 - 5)
        $sections = [];

        for ($index = 1; $index <= 5; $index++) {
            $sections[] = [
                'title' => $translation
                    ?->getAttribute("title_{$index}"),

                'description' => $translation
                    ?->getAttribute("description_{$index}"),
            ];
        }

        return [
            'id' => $service->id,

            'slug' => $translation?->slug,

            'lang' => $translation?->locale ?? $locale,

            'name' => $translation?->name,

            'description' => $translation?->description,

            'sections' => $sections,

            'images' => [
                'image_1' => $service->image_url,
                'image_2' => $service->image2_url,
            ],

            'is_active' => (bool) $service->is_active,

            'created_at' => $service->created_at
                ?->format('Y-m-d H:i:s'),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Service Not Found
    |--------------------------------------------------------------------------
    */

    private function serviceNotFound(): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' =>
                'Service not found for this language and slug',
        ], 404);
    }
}
