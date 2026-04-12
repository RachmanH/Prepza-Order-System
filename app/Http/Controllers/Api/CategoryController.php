<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Get all categories (admin)
     */
    public function adminIndex()
    {
        $categories = Category::withCount('menus')->orderBy('name')->get();
        
        return response()->json([
            'data' => $categories->map(function($cat) {
                return $this->serializeCategory($cat);
            }),
        ]);
    }

    /**
     * Get all active categories (for public/kiosk)
     */
    public function index()
    {
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        
        return response()->json([
            'data' => $categories->map(function($cat) {
                return $this->serializeCategory($cat);
            }),
        ]);
    }

    /**
     * Store a new category
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:categories',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $category = Category::create($validated);

        return response()->json([
            'data' => $this->serializeCategory($category),
            'message' => 'Kategori berhasil dibuat.',
        ], 201);
    }

    /**
     * Update a category
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => "required|string|max:100|unique:categories,name,{$category->id}",
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $category->update($validated);

        return response()->json([
            'data' => $this->serializeCategory($category),
            'message' => 'Kategori berhasil diperbarui.',
        ]);
    }

    /**
     * Delete a category
     */
    public function destroy(Category $category)
    {
        // Check if category has menus
        $menuCount = $category->menus()->count();
        
        if ($menuCount > 0) {
            return response()->json([
                'message' => "Tidak dapat menghapus kategori - masih ada {$menuCount} menu.",
            ], 422);
        }

        $category->delete();

        return response()->json([
            'message' => 'Kategori berhasil dihapus.',
        ]);
    }

    /**
     * Serialize category for API response
     */
    private function serializeCategory($category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'is_active' => $category->is_active,
            'menu_count' => $category->menus_count ?? $category->menus()->count(),
            'created_at' => $category->created_at,
        ];
    }
}
