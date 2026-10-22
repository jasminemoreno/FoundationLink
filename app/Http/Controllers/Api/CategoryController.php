<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use App\Models\Campaign;
use App\Support\CategoryIcon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CategoryController extends Controller
{
    /* =========================
       GET ALL CATEGORIES
       (with campaign/foundation counts + raised totals)
    ========================= */
    public function index()
    {
        $categories = Category::query()
            ->addSelect([
                'campaigns_count' => Campaign::selectRaw('count(*)')
                    ->whereColumn('category_id', 'categories.id')
            ])
            ->addSelect([
                'foundations_count' => Campaign::selectRaw('count(distinct foundation_id)')
                    ->whereColumn('category_id', 'categories.id')
            ])
            ->addSelect([
                'total_raised' => Campaign::selectRaw('coalesce(sum(current_amount), 0)')
                    ->whereColumn('category_id', 'categories.id')
            ])
            ->latest()
            ->get();

        // cast raw subquery results (come back as strings from PDO)
        $categories->each(function ($cat) {
            $cat->campaigns_count = (int) $cat->campaigns_count;
            $cat->foundations_count = (int) $cat->foundations_count;
            $cat->total_raised = (float) $cat->total_raised;
        });

        $grandTotal = $categories->sum('total_raised') ?: 1; // avoid div-by-zero

        $categories->each(function ($cat) use ($grandTotal) {
            $cat->percentage = round(($cat->total_raised / $grandTotal) * 100, 1);
        });

        return response()->json($categories);
    }

    /* =========================
       CREATE CATEGORY
    ========================= */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:20',
            // Chosen by the super admin; empty = pick one from the name
            'icon' => 'nullable|string|max:16',
        ]);

        $category = Category::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'color' => $validated['color'] ?? '#0F2D52',
            'icon' => $validated['icon'] ?? CategoryIcon::for($validated['name']),
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Category created successfully',
            'category' => $category
        ], 201);
    }

    /* =========================
       SHOW SINGLE CATEGORY
    ========================= */
    public function show($id)
    {
        return response()->json(
            Category::findOrFail($id)
        );
    }

    /* =========================
       UPDATE CATEGORY
    ========================= */
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:20',
            'icon' => 'nullable|string|max:16',
            'is_active' => 'boolean',
        ]);

        // Renamed and no icon chosen: if the current icon was picked automatically,
        // pick a new one for the new name. An icon the super admin chose is kept.
        $renamed = isset($validated['name']) && $validated['name'] !== $category->name;
        if ($renamed && empty($validated['icon']) && CategoryIcon::isAutomatic($category->icon, $category->name)) {
            $validated['icon'] = CategoryIcon::for($validated['name']);
        }
        if (array_key_exists('icon', $validated) && empty($validated['icon'])) {
            unset($validated['icon']); // an empty value never wipes the icon
        }

        $category->update($validated);

        return response()->json([
            'message' => 'Category updated successfully',
            'category' => $category
        ]);
    }

    /* =========================
       DELETE CATEGORY
    ========================= */
    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully'
        ]);
    }

    /* =========================
       ICON PREVIEW (as the super admin types a name)
       + the icons they can choose from instead
    ========================= */
    public function suggestIcon(Request $request)
    {
        return response()->json([
            'icon' => CategoryIcon::for($request->query('name', '')),
            'choices' => CategoryIcon::CHOICES,
        ]);
    }
}