<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServicesController extends Controller
{
    private const LANGUAGES = ['fa', 'en', 'de', 'fr', 'es', 'ar'];

    // GET /api/Services?lang=en
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lang' => ['sometimes', 'required', 'string', Rule::in(self::LANGUAGES)],
        ]);

        $locale = $validated['lang'] ?? app()->getLocale();
        $locales = array_values(array_unique([$locale, 'en']));

        $services = Service::query()
            ->where('is_active', true)
            ->with([
                'translations' => fn ($query) => $query->whereIn('locale', $locales),
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Service $service): array => $this->formatService($service, $locale))
            ->values();

        return response()->json(['status' => true, 'data' => $services]);
    }

    // GET /api/Services/en/website-design
    public function show(string $lang, string $slug): JsonResponse
    {
        if (!in_array($lang, self::LANGUAGES, true)) {
            return $this->serviceNotFound();
        }

        $service = Service::query()
            ->where('is_active', true)
            ->whereHas('translations', fn ($query) => $query
                ->where('locale', $lang)
                ->where('slug', $slug))
            ->with([
                'translations' => fn ($query) => $query->whereIn('locale', array_unique([$lang, 'en'])),
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

    private function formatService(Service $service, string $locale): array
    {
        $translation = $service->translations->firstWhere('locale', $locale)
            ?? $service->translations->firstWhere('locale', 'en');

        return [
            'id' => $service->id,
            'slug' => $translation?->slug,
            'lang' => $translation?->locale ?? $locale,
            'name' => $translation?->name,
            'description' => $translation?->description,
            'short_description' => $translation?->short_description,
            'images' => [
                'image_1' => $service->image_url,
                'image_2' => $service->image2_url,
            ],
            'deliverables' => $translation?->deliverables ?? [],
            'process' => $translation?->process ?? [],
            'approach_description' => $translation?->approach_description,
            'sections' => $translation?->sections ?? [],
            'is_active' => (bool) $service->is_active,
            'created_at' => $service->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    private function serviceNotFound(): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => 'Service not found for this language and slug',
        ], 404);
    }
}
