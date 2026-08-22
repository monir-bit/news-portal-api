<?php

namespace App\Http\Controllers;

use App\Models\StaticPage;
use Illuminate\Http\JsonResponse;

class PageController extends Controller
{
    /**
     * Get page content by name (terms, about, contact, privacy).
     */
    public function show(string $name): JsonResponse
    {
        $page = StaticPage::where('name', $name)->first();

        if (! $page) {
            return response()->json([
                'data' => null,
                'message' => 'Page not found',
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $page->id,
                'name' => $page->name,
                'content' => $page->content,
            ],
        ]);
    }

    /**
     * Get all static pages (for sitemap etc).
     */
    public function index(): JsonResponse
    {
        $pages = StaticPage::select('id', 'name', 'updated_at')->get();

        return response()->json([
            'data' => $pages,
        ]);
    }
}
