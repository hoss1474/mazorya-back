<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectTranslation;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    /**
     * گرفتن همه پروژه‌های فعال با ترجمه بر اساس زبان فعلی
     */
    public function index(Request $request)
    {
        $lang = $request->get('lang', app()->getLocale());

        $projects = Project::with(['translations' => function ($query) use ($lang) {
            $query->where('lang', $lang);
        }])
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($project) use ($lang) {
                $translation = $project->translation($lang);

                return [
                    'id' => $project->id,
                    'name' => $translation?->name,
                    'slug' => $translation?->slug, // اضافه شد
                    'description' => $translation?->description,
                    'sections' => [
                        [
                            'title' => $translation?->title_1,
                            'description' => $translation?->description_1,
                        ],
                        [
                            'title' => $translation?->title_2,
                            'description' => $translation?->description_2,
                        ],
                        [
                            'title' => $translation?->title_3,
                            'description' => $translation?->description_3,
                        ],
                        [
                            'title' => $translation?->title_4,
                            'description' => $translation?->description_4,
                        ],
                    ],
                    'images' => [
                        'image' => $project->image_url,
                        'image2' => $project->image2_url,
                    ],
                    'url' => $project->url,
                    'is_active' => $project->is_active,
                    'created_at' => $project->created_at,
                    'updated_at' => $project->updated_at,
                ];
            });

        return response()->json([
            'status' => true,
            'data' => $projects,
        ]);
    }

    /**
     * گرفتن جزئیات یک پروژه با ترجمه بر اساس زبان و slug
     */
    public function show(Request $request, $lang, $slug)
    {
        // ۱. پیدا کردن ترجمه به همراه مدل اصلی پروژه
        // استفاده از with('project') باعث می‌شود کوئری اضافه برای هر فیلد زده نشود
        $translation = ProjectTranslation::with('project')
            ->where('slug', $slug)
            ->where('lang', $lang)
            ->first();

        // ۲. بررسی وجود ترجمه و وضعیت فعال بودن پروژه
        // اگر ترجمه پیدا نشد یا پروژه مربوطه وجود نداشت یا غیرفعال بود -> 404
        if (!$translation || !$translation->project || !$translation->project->is_active) {
            return response()->json([
                'status' => false,
                'message' => 'Project not found or is inactive.',
            ], 404);
        }

        $project = $translation->project;

        // ۳. بازگرداندن پاسخ استاندارد
        return response()->json([
            'status' => true,
            'data' => [
                'id'            => $project->id,
                'name'          => $translation->name,
                'slug'          => $translation->slug,
                'description'   => $translation->description,
                'sections'      => [
                    [
                        'title'       => $translation->title_1,
                        'description' => $translation->description_1,
                    ],
                    [
                        'title'       => $translation->title_2,
                        'description' => $translation->description_2,
                    ],
                    [
                        'title'       => $translation->title_3,
                        'description' => $translation->description_3,
                    ],
                    [
                        'title'       => $translation->title_4,
                        'description' => $translation->description_4,
                    ],
                ],
                'images' => [
                    'image'  => $project->image_url,  // فرض بر این است که accessor در مدل Project داری
                    'image2' => $project->image2_url,
                ],
                'url'           => $project->url,
                'is_active'     => $project->is_active,
                'created_at'    => $project->created_at,
                'updated_at'    => $project->updated_at,
            ],
        ]);
    }
}
