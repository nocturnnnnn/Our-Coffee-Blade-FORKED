<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\MenuBadge;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\MenuItem;
use App\Models\MenuItemIngredient;
use App\Models\ModifierGroup;
use App\Services\BomCostService;
use App\Services\MenuAvailabilityService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MenuItemController extends Controller
{
    public function __construct(
        private readonly BomCostService $bomCostService,
        private readonly MenuAvailabilityService $availabilityService,
    ) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $categoryId = $request->query('category_id');
        $selectedCategoryId = is_numeric($categoryId) ? (int) $categoryId : null;

        $menuItems = MenuItem::query()
            ->with(['category', 'modifierGroups', 'recipeItems.ingredient'])
            ->when($search !== '', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))
            ->when($selectedCategoryId !== null, fn ($q) => $q->where('category_id', $selectedCategoryId))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $availabilityMap = $this->availabilityService->getAvailabilityMap($menuItems);

        $menusData = $menuItems->map(function (MenuItem $item) use ($availabilityMap): array {
            $cogs = $this->bomCostService->calculateCogs($item);
            $margin = $this->bomCostService->calculateGrossMargin($item->price, $cogs);
            $availability = $availabilityMap[$item->id] ?? [
                'is_available' => $item->is_available,
                'reason' => null,
                'max_servings' => null,
            ];

            return [
                'id' => $item->id,
                'category_id' => $item->category_id,
                'category_name' => $item->category->name,
                'name' => $item->name,
                'slug' => $item->slug,
                'description' => $item->description,
                'price' => $item->price,
                'image_url' => $item->image_path !== null ? asset('storage/'.$item->image_path) : asset('images/dummy-coffee.svg'),
                'badge' => $item->badge?->value,
                'is_available' => $item->is_available,
                'is_active' => $item->is_active,
                'sort_order' => $item->sort_order,
                'cogs' => $margin['cogs'],
                'gross_profit' => $margin['gross_profit'],
                'gross_margin_percent' => $margin['gross_margin_percent'],
                'effective_available' => $availability['is_available'],
                'unavailable_reason' => $availability['reason'],
                'max_servings' => $availability['max_servings'],
                'modifier_group_ids' => $item->modifierGroups->pluck('id')->values()->all(),
                'recipe_items' => $item->recipeItems->map(fn (MenuItemIngredient $recipe): array => [
                    'ingredient_id' => $recipe->ingredient_id,
                    'ingredient_name' => $recipe->ingredient->name,
                    'unit' => $recipe->ingredient->unit->value,
                    'cost_per_unit' => $recipe->ingredient->cost_per_unit,
                    'quantity' => (float) $recipe->quantity,
                ])->values()->all(),
            ];
        })->values()->all();

        $categories = Category::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'is_active'])
            ->map(fn (Category $cat): array => [
                'id' => $cat->id,
                'name' => $cat->name,
                'is_active' => $cat->is_active,
            ]);

        $modifierGroups = ModifierGroup::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'selection_type', 'is_required'])
            ->map(fn (ModifierGroup $group): array => [
                'id' => $group->id,
                'name' => $group->name,
                'selection_type' => $group->selection_type->value,
                'is_required' => $group->is_required,
            ]);

        $ingredients = Ingredient::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'unit', 'cost_per_unit'])
            ->map(fn (Ingredient $ingredient): array => [
                'id' => $ingredient->id,
                'name' => $ingredient->name,
                'unit' => $ingredient->unit->value,
                'cost_per_unit' => $ingredient->cost_per_unit,
            ]);

        return view('admin.menus.index', [
            'menus' => $menusData,
            'categories' => $categories->values()->all(),
            'modifierGroups' => $modifierGroups->values()->all(),
            'ingredients' => $ingredients->values()->all(),
            'filters' => [
                'search' => $search,
                'category_id' => $selectedCategoryId,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateMenu($request);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            if ($file instanceof UploadedFile) {
                $imagePath = $file->store('menu-items', 'public');
            }
        }

        $name = (string) $validated['name'];

        DB::transaction(function () use ($validated, $name, $imagePath): void {
            $menuItem = MenuItem::query()->create([
                'category_id' => (int) $validated['category_id'],
                'name' => $name,
                'slug' => $this->generateUniqueSlug($name),
                'description' => isset($validated['description']) && $validated['description'] !== '' ? (string) $validated['description'] : null,
                'price' => (int) $validated['price'],
                'image_path' => is_string($imagePath) ? $imagePath : null,
                'badge' => isset($validated['badge']) && $validated['badge'] !== '' ? (string) $validated['badge'] : null,
                'is_available' => (bool) ($validated['is_available'] ?? true),
                'is_active' => (bool) ($validated['is_active'] ?? true),
                'sort_order' => (int) ($validated['sort_order'] ?? 0),
            ]);

            $this->syncModifierGroups($menuItem, $validated['modifier_group_ids'] ?? []);
        });

        return redirect()->back()->with('success', 'Menu berhasil ditambahkan.');
    }

    public function update(Request $request, MenuItem $menuItem): RedirectResponse
    {
        $validated = $this->validateMenu($request, isUpdate: true);

        $imagePath = $menuItem->image_path;

        if ((bool) ($validated['remove_image'] ?? false) && $imagePath !== null) {
            Storage::disk('public')->delete($imagePath);
            $imagePath = null;
        }

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            if ($file instanceof UploadedFile) {
                if ($imagePath !== null) {
                    Storage::disk('public')->delete($imagePath);
                }
                $stored = $file->store('menu-items', 'public');
                $imagePath = is_string($stored) ? $stored : $imagePath;
            }
        }

        $name = (string) $validated['name'];

        DB::transaction(function () use ($menuItem, $validated, $name, $imagePath): void {
            $menuItem->forceFill([
                'category_id' => (int) $validated['category_id'],
                'name' => $name,
                'slug' => $this->generateUniqueSlug($name, $menuItem->id),
                'description' => isset($validated['description']) && $validated['description'] !== '' ? (string) $validated['description'] : null,
                'price' => (int) $validated['price'],
                'image_path' => $imagePath,
                'badge' => isset($validated['badge']) && $validated['badge'] !== '' ? (string) $validated['badge'] : null,
                'is_available' => (bool) ($validated['is_available'] ?? true),
                'is_active' => (bool) ($validated['is_active'] ?? true),
                'sort_order' => (int) ($validated['sort_order'] ?? 0),
            ])->save();

            $this->syncModifierGroups($menuItem, $validated['modifier_group_ids'] ?? []);
        });

        return redirect()->back()->with('success', 'Menu berhasil diperbarui.');
    }

    public function toggleAvailability(MenuItem $menuItem): RedirectResponse
    {
        $menuItem->forceFill([
            'is_available' => ! $menuItem->is_available,
        ])->save();

        $statusLabel = $menuItem->is_available ? 'Tersedia' : 'Sold Out (Manual)';

        return redirect()->back()->with('success', "Status {$menuItem->name} diubah menjadi {$statusLabel}.");
    }

    public function updateRecipe(Request $request, MenuItem $menuItem): RedirectResponse
    {
        $validated = $request->validate([
            'ingredients' => ['present', 'array'],
            'ingredients.*.ingredient_id' => ['required', 'integer', 'distinct', 'exists:ingredients,id'],
            'ingredients.*.quantity' => ['required', 'numeric', 'gt:0'],
        ]);

        /** @var array<int, array{ingredient_id: int|string, quantity: float|int|string}> $rows */
        $rows = $validated['ingredients'];

        DB::transaction(function () use ($menuItem, $rows): void {
            MenuItemIngredient::query()->where('menu_item_id', $menuItem->id)->delete();

            foreach ($rows as $row) {
                MenuItemIngredient::query()->create([
                    'menu_item_id' => $menuItem->id,
                    'ingredient_id' => (int) $row['ingredient_id'],
                    'quantity' => number_format((float) $row['quantity'], 3, '.', ''),
                ]);
            }
        });

        return redirect()->back()->with('success', "Resep BOM {$menuItem->name} berhasil disimpan.");
    }

    public function destroy(MenuItem $menuItem): RedirectResponse
    {
        $menuItem->delete();

        return redirect()->back()->with('success', 'Menu berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateMenu(Request $request, bool $isUpdate = false): array
    {
        $rules = [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'integer', 'min:0'],
            'badge' => ['nullable', Rule::enum(MenuBadge::class)],
            'is_available' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'modifier_group_ids' => ['nullable', 'array'],
            'modifier_group_ids.*' => ['integer', 'exists:modifier_groups,id'],
        ];

        if ($isUpdate) {
            $rules['remove_image'] = ['boolean'];
        }

        return $request->validate($rules);
    }

    /**
     * @param  array<int, int|string>  $groupIds
     */
    private function syncModifierGroups(MenuItem $menuItem, array $groupIds): void
    {
        $syncPayload = [];
        foreach (array_values($groupIds) as $index => $groupId) {
            $syncPayload[(int) $groupId] = ['sort_order' => $index + 1];
        }

        $menuItem->modifierGroups()->sync($syncPayload);
    }

    private function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'menu';
        }

        $slug = $base;
        $counter = 1;

        while (
            MenuItem::withTrashed()
                ->where('slug', $slug)
                ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
