<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    private const LANGUAGES = [
        'fa', 'en', 'de', 'fr', 'es', 'ar',
    ];

    /*
    |--------------------------------------------------------------------------
    | GET /api/blogs
    | GET /api/blogs?lang=en
    | GET /api/blogs?lang=en&limit=4
    |--------------------------------------------------------------------------
    */

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lang' => [
                'sometimes', 'required', 'string',
                'in:fa,en,de,fr,es,ar',
            ],
            'locale' => [
                'sometimes', 'required', 'string',
                'in:fa,en,de,fr,es,ar',
            ],
            'limit' => [
                'sometimes', 'required', 'integer',
                'min:1', 'max:100',
            ],
        ]);

        $locale = $validated['lang']
            ?? $validated['locale']
            ?? app()->getLocale();

        $query = Blog::query()
            ->with([
                'translations' => fn ($query) =>
                $query->where('locale', $locale),
            ])
            ->where('is_active', true)
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if (isset($validated['limit'])) {
            $query->limit((int) $validated['limit']);
        }

        $blogs = $query
            ->get()
            ->map(
                fn (Blog $blog) =>
                $this->formatBlog($blog, $locale)
            )
            ->values();

        return response()->json([
            'status' => true,
            'data' => $blogs,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/blogs/{id}
    | GET /api/blogs/12
    | GET /api/blogs/12?lang=en
    |--------------------------------------------------------------------------
    */

    public function showById(
        Request $request,
        string $id
    ): JsonResponse {
        $validated = $request->validate([
            'lang' => [
                'sometimes', 'required', 'string',
                'in:fa,en,de,fr,es,ar',
            ],
            'locale' => [
                'sometimes', 'required', 'string',
                'in:fa,en,de,fr,es,ar',
            ],
        ]);

        $locale = $validated['lang']
            ?? $validated['locale']
            ?? app()->getLocale();

        $blog = Blog::query()
            ->where('is_active', true)
            ->with([
                'translations' => fn ($query) =>
                $query->where('locale', $locale),
            ])
            ->find($id);

        if (!$blog) {
            return $this->blogNotFound();
        }

        return response()->json([
            'status' => true,
            'data' => $this->formatBlog($blog, $locale),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/blogs/{lang}/{slug}
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        string $lang,
        string $slug
    ): JsonResponse {
        if (!in_array($lang, self::LANGUAGES, true)) {
            return $this->blogNotFound();
        }

        $blog = Blog::query()
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
                $query->where('locale', $lang),
            ])
            ->first();

        if (!$blog) {
            return $this->blogNotFound();
        }

        return response()->json([
            'status' => true,
            'data' => $this->formatBlog($blog, $lang),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Format Blog
    |--------------------------------------------------------------------------
    */

    private function formatBlog(
        Blog $blog,
        string $locale
    ): array {
        $translation = $blog->translations
            ->firstWhere('locale', $locale);

        $sections = [];

        for ($i = 1; $i <= 5; $i++) {
            $sections[] = [
                'title' => $translation
                    ?->getAttribute("title_{$i}"),

                'description' => $translation
                    ?->getAttribute("description_{$i}"),
            ];
        }

        return [
            'id' => $blog->id,

            'slug' => $translation?->slug,

            'lang' => $locale,

            'title' => $translation?->title,

            'description' => $translation?->description,

            'sections' => $sections,

            'images' => [
                'image_1' => $blog->image1_url,
                'image_2' => $blog->image2_url,
                'image_3' => $blog->image3_url,
            ],

            'is_active' => (bool) $blog->is_active,

            'created_at' => $blog->created_at
                ?->format('Y-m-d H:i:s'),

            'updated_at' => $blog->updated_at
                ?->format('Y-m-d H:i:s'),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Error Response
    |--------------------------------------------------------------------------
    */

    private function blogNotFound(): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => 'Blog not found or is inactive.',
        ], 404);
    }
}
