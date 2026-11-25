<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ModifierSelectionType;
use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\ModifierGroup;
use App\Models\ModifierOption;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ModifierGroupController extends Controller
{
    public function index(): View
    {
        $groups = ModifierGroup::query()
            ->with(['options.ingredient'])
            ->withCount('menuItems')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (ModifierGroup $group): array => [
                'id' => $group->id,
                'name' => $group->name,
                'selection_type' => $group->selection_type->value,
                'is_required' => $group->is_required,
                'min_select' => $group->min_select,
                'max_select' => $group->max_select,
                'sort_order' => $group->sort_order,
                'menu_items_count' => (int) ($group->menu_items_count ?? 0),
                'options' => $group->options->map(fn (ModifierOption $option): array => [
                    'id' => $option->id,
                    'modifier_group_id' => $option->modifier_group_id,
                    'name' => $option->name,
                    'price_delta' => $option->price_delta,
                    'is_default' => $option->is_default,
                    'is_available' => $option->is_available,
                    'ingredient_id' => $option->ingredient_id,
                    'ingredient_name' => $option->ingredient?->name,
                    'ingredient_unit' => $option->ingredient?->unit->value,
                    'ingredient_qty' => $option->ingredient_qty !== null ? (float) $option->ingredient_qty : null,
                    'sort_order' => $option->sort_order,
                ])->values()->all(),
            ]);

        $ingredients = Ingredient::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'unit'])
            ->map(fn (Ingredient $ingredient): array => [
                'id' => $ingredient->id,
                'name' => $ingredient->name,
                'unit' => $ingredient->unit->value,
            ]);

        return view('admin.modifiers.index', [
            'modifierGroups' => $groups->values()->all(),
            'ingredients' => $ingredients->values()->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateGroup($request);
        $minSelect = (int) ($validated['min_select'] ?? 0);
        $maxSelect = max(1, (int) ($validated['max_select'] ?? 1), $minSelect);

        ModifierGroup::query()->create([
            'name' => (string) $validated['name'],
            'selection_type' => (string) $validated['selection_type'],
            'is_required' => (bool) ($validated['is_required'] ?? false),
            'min_select' => $minSelect,
            'max_select' => $maxSelect,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ]);

        return redirect()->back()->with('success', 'Grup modifier berhasil ditambahkan.');
    }

    public function update(Request $request, ModifierGroup $modifierGroup): RedirectResponse
    {
        $validated = $this->validateGroup($request);
        $minSelect = (int) ($validated['min_select'] ?? 0);
        $maxSelect = max(1, (int) ($validated['max_select'] ?? 1), $minSelect);

        $modifierGroup->forceFill([
            'name' => (string) $validated['name'],
            'selection_type' => (string) $validated['selection_type'],
            'is_required' => (bool) ($validated['is_required'] ?? false),
            'min_select' => $minSelect,
            'max_select' => $maxSelect,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ])->save();

        return redirect()->back()->with('success', 'Grup modifier berhasil diperbarui.');
    }

    public function destroy(ModifierGroup $modifierGroup): RedirectResponse
    {
        $modifierGroup->delete();

        return redirect()->back()->with('success', 'Grup modifier berhasil dihapus.');
    }

    public function storeOption(Request $request, ModifierGroup $modifierGroup): RedirectResponse
    {
        $validated = $this->validateOption($request);

        DB::transaction(function () use ($modifierGroup, $validated): void {
            $isDefault = (bool) ($validated['is_default'] ?? false);

            if ($isDefault && $modifierGroup->selection_type === ModifierSelectionType::Single) {
                ModifierOption::query()
                    ->where('modifier_group_id', $modifierGroup->id)
                    ->update(['is_default' => false]);
            }

            $ingredientId = isset($validated['ingredient_id'])
                ? (int) $validated['ingredient_id']
                : null;

            ModifierOption::query()->create([
                'modifier_group_id' => $modifierGroup->id,
                'name' => (string) $validated['name'],
                'price_delta' => (int) $validated['price_delta'],
                'is_default' => $isDefault,
                'is_available' => (bool) ($validated['is_available'] ?? true),
                'ingredient_id' => $ingredientId,
                'ingredient_qty' => $ingredientId !== null && isset($validated['ingredient_qty'])
                    ? number_format((float) $validated['ingredient_qty'], 3, '.', '')
                    : null,
                'sort_order' => (int) ($validated['sort_order'] ?? 0),
            ]);
        });

        return redirect()->back()->with('success', 'Opsi modifier berhasil ditambahkan.');
    }

    public function updateOption(
        Request $request,
        ModifierGroup $modifierGroup,
        ModifierOption $modifierOption,
    ): RedirectResponse {
        if ($modifierOption->modifier_group_id !== $modifierGroup->id) {
            abort(404);
        }

        $validated = $this->validateOption($request);

        DB::transaction(function () use ($modifierGroup, $modifierOption, $validated): void {
            $isDefault = (bool) ($validated['is_default'] ?? false);

            if ($isDefault && $modifierGroup->selection_type === ModifierSelectionType::Single) {
                ModifierOption::query()
                    ->where('modifier_group_id', $modifierGroup->id)
                    ->where('id', '!=', $modifierOption->id)
                    ->update(['is_default' => false]);
            }

            $ingredientId = isset($validated['ingredient_id'])
                ? (int) $validated['ingredient_id']
                : null;

            $modifierOption->forceFill([
                'name' => (string) $validated['name'],
                'price_delta' => (int) $validated['price_delta'],
                'is_default' => $isDefault,
                'is_available' => (bool) ($validated['is_available'] ?? true),
                'ingredient_id' => $ingredientId,
                'ingredient_qty' => $ingredientId !== null && isset($validated['ingredient_qty'])
                    ? number_format((float) $validated['ingredient_qty'], 3, '.', '')
                    : null,
                'sort_order' => (int) ($validated['sort_order'] ?? 0),
            ])->save();
        });

        return redirect()->back()->with('success', 'Opsi modifier berhasil diperbarui.');
    }

    public function destroyOption(ModifierGroup $modifierGroup, ModifierOption $modifierOption): RedirectResponse
    {
        if ($modifierOption->modifier_group_id !== $modifierGroup->id) {
            abort(404);
        }

        $modifierOption->delete();

        return redirect()->back()->with('success', 'Opsi modifier berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateGroup(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'selection_type' => ['required', Rule::enum(ModifierSelectionType::class)],
            'is_required' => ['boolean'],
            'min_select' => ['nullable', 'integer', 'min:0', 'max:20'],
            'max_select' => ['nullable', 'integer', 'min:1', 'max:20'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateOption(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'price_delta' => ['required', 'integer', 'min:0'],
            'is_default' => ['boolean'],
            'is_available' => ['boolean'],
            'ingredient_id' => ['nullable', 'integer', 'exists:ingredients,id'],
            'ingredient_qty' => ['nullable', 'numeric', 'gt:0', 'required_with:ingredient_id'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);
    }
}
