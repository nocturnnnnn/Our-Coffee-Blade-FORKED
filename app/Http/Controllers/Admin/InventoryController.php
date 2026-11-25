<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\IngredientUnit;
use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Models\Distributor;
use App\Models\Ingredient;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    public function index(Request $request): View
    {
        $filterIngredientId = $request->query('ingredient_id');
        $selectedIngredientId = is_numeric($filterIngredientId) ? (int) $filterIngredientId : null;
        $selectedType = trim((string) $request->query('type', ''));

        $ingredients = Ingredient::query()
            ->with('defaultDistributor')
            ->orderByRaw('CASE WHEN current_stock <= min_stock THEN 0 ELSE 1 END')
            ->orderBy('name')
            ->get()
            ->map(fn (Ingredient $ingredient): array => [
                'id' => $ingredient->id,
                'name' => $ingredient->name,
                'sku' => $ingredient->sku,
                'unit' => $ingredient->unit->value,
                'current_stock' => (float) $ingredient->current_stock,
                'min_stock' => (float) $ingredient->min_stock,
                'cost_per_unit' => $ingredient->cost_per_unit,
                'default_distributor_id' => $ingredient->default_distributor_id,
                'default_distributor_name' => $ingredient->defaultDistributor?->name,
                'is_active' => $ingredient->is_active,
                'is_low_stock' => $ingredient->isLowStock(),
            ]);

        $distributors = Distributor::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Distributor $distributor): array => [
                'id' => $distributor->id,
                'name' => $distributor->name,
            ]);

        $movements = StockMovement::query()
            ->with(['ingredient', 'distributor', 'creator'])
            ->when($selectedIngredientId !== null, fn ($q) => $q->where('ingredient_id', $selectedIngredientId))
            ->when($selectedType !== '', fn ($q) => $q->where('type', $selectedType))
            ->orderByDesc('id')
            ->limit(60)
            ->get()
            ->map(fn (StockMovement $movement): array => [
                'id' => $movement->id,
                'ingredient_id' => $movement->ingredient_id,
                'ingredient_name' => $movement->ingredient->name,
                'unit' => $movement->ingredient->unit->value,
                'type' => $movement->type->value,
                'type_label' => $movement->type->label(),
                'quantity_change' => (float) $movement->quantity_change,
                'stock_before' => (float) $movement->stock_before,
                'stock_after' => (float) $movement->stock_after,
                'unit_cost' => $movement->unit_cost,
                'total_cost' => $movement->total_cost,
                'distributor_name' => $movement->distributor?->name,
                'notes' => $movement->notes,
                'creator_name' => $movement->creator?->name,
                'created_at' => $movement->created_at?->toIso8601String(),
            ]);

        return view('admin.inventory.index', [
            'ingredients' => $ingredients->values()->all(),
            'distributors' => $distributors->values()->all(),
            'movements' => $movements->values()->all(),
            'filters' => [
                'ingredient_id' => $selectedIngredientId,
                'type' => $selectedType,
            ],
        ]);
    }

    public function storeIngredient(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'sku' => ['nullable', 'string', 'max:50', 'unique:ingredients,sku'],
            'unit' => ['required', Rule::enum(IngredientUnit::class)],
            'current_stock' => ['required', 'numeric', 'min:0'],
            'min_stock' => ['required', 'numeric', 'min:0'],
            'cost_per_unit' => ['required', 'integer', 'min:0'],
            'default_distributor_id' => ['nullable', 'integer', 'exists:distributors,id'],
            'is_active' => ['boolean'],
        ]);

        $userId = $request->user()?->id;

        DB::transaction(function () use ($validated, $userId): void {
            $initialStock = (float) $validated['current_stock'];
            $costPerUnit = (int) $validated['cost_per_unit'];
            $distributorId = isset($validated['default_distributor_id'])
                ? (int) $validated['default_distributor_id']
                : null;

            $ingredient = Ingredient::query()->create([
                'name' => (string) $validated['name'],
                'sku' => isset($validated['sku']) && $validated['sku'] !== '' ? (string) $validated['sku'] : null,
                'unit' => (string) $validated['unit'],
                'current_stock' => number_format($initialStock, 3, '.', ''),
                'min_stock' => number_format((float) $validated['min_stock'], 3, '.', ''),
                'cost_per_unit' => $costPerUnit,
                'default_distributor_id' => $distributorId,
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ]);

            if ($initialStock > 0) {
                StockMovement::query()->create([
                    'ingredient_id' => $ingredient->id,
                    'type' => StockMovementType::Restock,
                    'quantity_change' => number_format($initialStock, 3, '.', ''),
                    'stock_before' => '0.000',
                    'stock_after' => number_format($initialStock, 3, '.', ''),
                    'unit_cost' => $costPerUnit,
                    'total_cost' => (int) round($initialStock * $costPerUnit),
                    'distributor_id' => $distributorId,
                    'notes' => 'Stok awal bahan baku baru',
                    'created_by' => $userId,
                    'created_at' => now('Asia/Jakarta'),
                ]);
            }
        });

        return redirect()->back()->with('success', 'Bahan baku berhasil ditambahkan.');
    }

    public function updateIngredient(Request $request, Ingredient $ingredient): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'sku' => ['nullable', 'string', 'max:50', Rule::unique('ingredients', 'sku')->ignore($ingredient->id)],
            'unit' => ['required', Rule::enum(IngredientUnit::class)],
            'min_stock' => ['required', 'numeric', 'min:0'],
            'cost_per_unit' => ['required', 'integer', 'min:0'],
            'default_distributor_id' => ['nullable', 'integer', 'exists:distributors,id'],
            'is_active' => ['boolean'],
        ]);

        $ingredient->forceFill([
            'name' => (string) $validated['name'],
            'sku' => isset($validated['sku']) && $validated['sku'] !== '' ? (string) $validated['sku'] : null,
            'unit' => (string) $validated['unit'],
            'min_stock' => number_format((float) $validated['min_stock'], 3, '.', ''),
            'cost_per_unit' => (int) $validated['cost_per_unit'],
            'default_distributor_id' => isset($validated['default_distributor_id'])
                ? (int) $validated['default_distributor_id']
                : null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ])->save();

        return redirect()->back()->with('success', 'Data bahan baku berhasil diperbarui.');
    }

    public function restock(Request $request, Ingredient $ingredient): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'numeric', 'gt:0'],
            'total_cost' => ['required', 'integer', 'min:0'],
            'distributor_id' => ['nullable', 'integer', 'exists:distributors,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $this->inventoryService->restock(
            ingredient: $ingredient,
            quantity: (float) $validated['quantity'],
            totalCost: (int) $validated['total_cost'],
            distributorId: isset($validated['distributor_id'])
                ? (int) $validated['distributor_id']
                : null,
            actorId: $request->user()?->id,
            notes: isset($validated['notes']) && $validated['notes'] !== '' ? (string) $validated['notes'] : null,
        );

        return redirect()->back()->with('success', "Stock In untuk {$ingredient->name} berhasil dicatat.");
    }

    public function waste(Request $request, Ingredient $ingredient): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'numeric', 'gt:0'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $actorId = $request->user()?->id;

        $this->inventoryService->recordWaste(
            ingredient: $ingredient,
            quantity: (float) $validated['quantity'],
            reason: (string) $validated['reason'],
            actorId: $actorId,
            authorizedById: $actorId,
        );

        return redirect()->back()->with('success', "Waste untuk {$ingredient->name} berhasil dicatat.");
    }

    public function opname(Request $request, Ingredient $ingredient): RedirectResponse
    {
        $validated = $request->validate([
            'actual_stock' => ['required', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $actorId = $request->user()?->id;

        $this->inventoryService->recordOpname(
            ingredient: $ingredient,
            actualStock: (float) $validated['actual_stock'],
            reason: (string) $validated['reason'],
            actorId: $actorId,
            authorizedById: $actorId,
        );

        return redirect()->back()->with('success', "Stock Opname untuk {$ingredient->name} berhasil dicatat.");
    }
}
