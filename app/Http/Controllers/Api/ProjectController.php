<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectTranslation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProjectController extends Controller
{
    private const LANGUAGES = ['fa', 'en', 'de', 'fr', 'es', 'ar'];

    /**
     * GET /api/projects?lang=en
     * GET /api/projects?lang=en&limit=6
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lang' => ['sometimes', 'required', 'string', 'in:fa,en,de,fr,es,ar'],
            'limit' => ['sometimes', 'required', 'integer', 'min:1', 'max:100'],
        ]);

        $lang = $validated['lang'] ?? app()->getLocale();

        // Only return projects that have a translation in the requested language.
        // This guarantees each returned slug can be used in /projects/{lang}/{slug}.
        $query = Project::query()
            ->where('is_active', true)
            ->whereHas('translations', fn ($query) => $query->where('lang', $lang))
            ->with([
                'translations' => fn ($query) => $query->where('lang', $lang),
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if (isset($validated['limit'])) {
            $query->limit((int) $validated['limit']);
        }

        $projects = $query->get()
            ->map(function (Project $project) use ($lang): array {
                $translation = $project->translations->firstWhere('lang', $lang);

                return $this->formatProject($project, $translation);
            })
            ->values();

        return response()->json([
            'status' => true,
            'data' => $projects,
        ]);
    }

    /**
     * GET /api/projects/en/nova-studio
     * GET /api/projects/en/34
     */
    public function show(string $lang, string $slug): JsonResponse
    {
        if (! in_array($lang, self::LANGUAGES, true)) {
            return $this->projectNotFound();
        }

        // Preserve the existing numeric-ID route behavior.
        if (ctype_digit($slug)) {
            $project = Project::query()
                ->where('is_active', true)
                ->with([
                    'translations' => fn ($query) => $query->where('lang', $lang),
                ])
                ->find($slug);

            $translation = $project?->translations->firstWhere('lang', $lang);

            if (! $project || ! $translation) {
                return $this->projectNotFound();
            }

            return response()->json([
                'status' => true,
                'data' => $this->formatProject($project, $translation),
            ]);
        }

        // A slug is unique per language, not globally.
        $translation = ProjectTranslation::query()
            ->with('project')
            ->where('lang', $lang)
            ->where('slug', $slug)
            ->whereHas('project', fn ($query) => $query->where('is_active', true))
            ->first();

        if (! $translation || ! $translation->project) {
            return $this->projectNotFound();
        }

        return response()->json([
            'status' => true,
            'data' => $this->formatProject($translation->project, $translation),
        ]);
    }

    /**
     * GET /api/projects/nova-studio?lang=en
     * GET /api/projects/34?lang=en
     */
    public function showDefault(Request $request, string $slug): JsonResponse
    {
        $validated = $request->validate([
            'lang' => ['sometimes', 'required', 'string', 'in:fa,en,de,fr,es,ar'],
        ]);

        return $this->show($validated['lang'] ?? app()->getLocale(), $slug);
    }

    /**
     * Flatten shared data and translated category content into the fields
     * that pages/projectDetails.js reads directly.
     */
    private function formatProject(
        Project $project,
        ProjectTranslation $translation
    ): array {
        $shared = is_array($project->category_data) ? $project->category_data : [];
        $localized = is_array($translation->category_content)
            ? $translation->category_content
            : [];

        return [
            'id' => $project->id,
            'lang' => $translation->lang,
            'name' => $translation->name,
            'slug' => $translation->slug,
            'category' => $this->normalizeCategory($project->category),
            'description' => $translation->description,
            'client' => $project->client,
            'year' => $project->year,
            'url' => $project->url,

            'images' => [
                'image' => $project->image_url,
                'image2' => $project->image2_url,
                'gallery' => $this->galleryUrls($project->gallery),
            ],

            // Website
            'technologies' => $this->arrayValue($project->technologies),

            // UI/UX
            'prototype_url' => $shared['prototype_url'] ?? null,
            'figma_url' => $shared['figma_url'] ?? null,
            'design_tools' => $this->arrayValue($shared['design_tools'] ?? null),
            'process' => $this->arrayValue($localized['process'] ?? null),

            // Social Media
            'campaign_url' => $shared['campaign_url'] ?? null,
            'platforms' => $this->arrayValue($shared['platforms'] ?? null),

            // Prompts
            'purchase_url' => $shared['purchase_url'] ?? null,
            'download_url' => $shared['download_url'] ?? null,
            'compatible_tools' => $this->arrayValue($shared['compatible_tools'] ?? null),
            'prompt_preview' => $localized['prompt_preview'] ?? null,

            // Other
            'features' => $this->arrayValue($localized['features'] ?? null),

            // Available to all categories
            'sections' => $this->arrayValue($translation->sections),
            'is_active' => (bool) $project->is_active,
            'is_marked' => (bool) $project->is_marked,
            'created_at' => $project->created_at,
            'updated_at' => $project->updated_at,
        ];
    }

    /** Resolve gallery paths using the same disk as the model image accessors. */
    private function galleryUrls(mixed $gallery): array
    {
        if (! is_array($gallery)) {
            return [];
        }

        $urls = [];

        foreach ($gallery as $path) {
            if (! is_string($path) || trim($path) === '') {
                continue;
            }

            $urls[] = url(Storage::disk('api_public')->url($path));
        }

        return array_values(array_unique($urls));
    }

    private function arrayValue(mixed $value): array
    {
        return is_array($value) ? array_values($value) : [];
    }

    /** Keep legacy category names compatible with the six frontend templates. */
    private function normalizeCategory(?string $category): string
    {
        return match (strtolower(trim($category ?? ''))) {
            'website', 'web' => 'website',
            'ui-ux', 'ui/ux', 'ui.ux', 'uiux' => 'ui-ux',
            'social-media', 'social media', 'socialmedia', 'social' => 'social-media',
            'prompts', 'prompt' => 'prompts',
            'graphic', 'graphic-design', 'graphic design', 'graphics' => 'graphic',
            default => 'other',
        };
    }

    private function projectNotFound(): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => 'Project not found, inactive, or missing a translation for this language.',
        ], 404);
    }
}
