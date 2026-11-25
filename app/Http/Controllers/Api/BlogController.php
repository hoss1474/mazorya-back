<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BlogController extends Controller
{
    private const LANGUAGES = ['fa', 'en', 'de', 'fr', 'es', 'ar'];

    // GET /api/blogs?lang=en[&limit=4]
    public function index(Request $request): JsonResponse
    {
        $validated = $this->validatedQuery($request, true);
        $locale = $this->localeFrom($validated);

        $query = Blog::query()
            ->where('is_active', true)
            ->whereHas('translations', fn ($query) => $query->where('locale', $locale))
            ->with([
                'translations' => fn ($query) => $query->where('locale', $locale),
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if (isset($validated['limit'])) {
            $query->limit((int) $validated['limit']);
        }

        $blogs = $query->get()
            ->map(fn (Blog $blog): array => $this->formatBlog($blog, $locale))
            ->values();

        return response()->json([
            'status' => true,
            'data' => $blogs,
        ]);
    }

    // GET /api/blogs/12?lang=en
    public function showById(Request $request, string $id): JsonResponse
    {
        $validated = $this->validatedQuery($request);
        $locale = $this->localeFrom($validated);

        if (!ctype_digit($id)) {
            return $this->blogNotFound();
        }

        $blog = Blog::query()
            ->where('is_active', true)
            ->whereHas('translations', fn ($query) => $query->where('locale', $locale))
            ->with([
                'translations' => fn ($query) => $query->where('locale', $locale),
            ])
            ->find($id);

        return $blog
            ? response()->json(['status' => true, 'data' => $this->formatBlog($blog, $locale)])
            : $this->blogNotFound();
    }

    // GET /api/blogs/en/article-slug
    public function show(Request $request, string $lang, string $slug): JsonResponse
    {
        if (!in_array($lang, self::LANGUAGES, true)) {
            return $this->blogNotFound();
        }

        $blog = Blog::query()
            ->where('is_active', true)
            ->whereHas('translations', fn ($query) => $query
                ->where('locale', $lang)
                ->where('slug', $slug))
            ->with([
                'translations' => fn ($query) => $query->where('locale', $lang),
            ])
            ->first();

        return $blog
            ? response()->json(['status' => true, 'data' => $this->formatBlog($blog, $lang)])
            : $this->blogNotFound();
    }

    private function validatedQuery(Request $request, bool $withLimit = false): array
    {
        $rules = [
            'lang' => ['sometimes', 'required', 'string', Rule::in(self::LANGUAGES)],
            'locale' => ['sometimes', 'required', 'string', Rule::in(self::LANGUAGES)],
        ];

        if ($withLimit) {
            $rules['limit'] = ['sometimes', 'required', 'integer', 'min:1', 'max:100'];
        }

        return $request->validate($rules);
    }

    private function localeFrom(array $validated): string
    {
        $fallback = app()->getLocale();

        if (!in_array($fallback, self::LANGUAGES, true)) {
            $fallback = 'en';
        }

        return $validated['lang'] ?? $validated['locale'] ?? $fallback;
    }

    private function formatBlog(Blog $blog, string $locale): array
    {
        $translation = $blog->translations->firstWhere('locale', $locale);

        return [
            'id' => $blog->id,
            'slug' => $translation?->slug,
            'lang' => $locale,
            'title' => $translation?->title,
            'description' => $translation?->description,
            'excerpt' => $translation?->excerpt,
            'published_at' => $blog->published_at
                ? \Illuminate\Support\Carbon::parse($blog->published_at)->toIso8601String()
                : null,
            'category_name' => $translation?->category_name,
            'images' => [
                'image_1' => $blog->image1_url,
                'image_2' => $blog->image2_url,
            ],
            'sections' => $translation?->sections ?? [],
            'quote' => $translation?->quote,
            'takeaways' => $translation?->takeaways ?? [],
            'is_active' => (bool) $blog->is_active,
            'created_at' => $blog->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $blog->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    private function blogNotFound(): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => 'Blog not found or is inactive for this language.',
        ], 404);
    }
}
