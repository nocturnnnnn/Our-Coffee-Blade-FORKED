<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Distributor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DistributorController extends Controller
{
    public function index(): View
    {
        $distributors = Distributor::query()
            ->withCount('ingredients')
            ->orderBy('name')
            ->get()
            ->map(fn (Distributor $distributor): array => [
                'id' => $distributor->id,
                'name' => $distributor->name,
                'contact_person' => $distributor->contact_person,
                'phone' => $distributor->phone,
                'email' => $distributor->email,
                'address' => $distributor->address,
                'notes' => $distributor->notes,
                'is_active' => $distributor->is_active,
                'ingredients_count' => (int) ($distributor->ingredients_count ?? 0),
            ]);

        return view('admin.distributors.index', [
            'distributors' => $distributors->values()->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateDistributor($request);

        Distributor::query()->create([
            'name' => (string) $validated['name'],
            'contact_person' => isset($validated['contact_person']) && $validated['contact_person'] !== '' ? (string) $validated['contact_person'] : null,
            'phone' => isset($validated['phone']) && $validated['phone'] !== '' ? (string) $validated['phone'] : null,
            'email' => isset($validated['email']) && $validated['email'] !== '' ? (string) $validated['email'] : null,
            'address' => isset($validated['address']) && $validated['address'] !== '' ? (string) $validated['address'] : null,
            'notes' => isset($validated['notes']) && $validated['notes'] !== '' ? (string) $validated['notes'] : null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return redirect()->back()->with('success', 'Distributor berhasil ditambahkan.');
    }

    public function update(Request $request, Distributor $distributor): RedirectResponse
    {
        $validated = $this->validateDistributor($request);

        $distributor->forceFill([
            'name' => (string) $validated['name'],
            'contact_person' => isset($validated['contact_person']) && $validated['contact_person'] !== '' ? (string) $validated['contact_person'] : null,
            'phone' => isset($validated['phone']) && $validated['phone'] !== '' ? (string) $validated['phone'] : null,
            'email' => isset($validated['email']) && $validated['email'] !== '' ? (string) $validated['email'] : null,
            'address' => isset($validated['address']) && $validated['address'] !== '' ? (string) $validated['address'] : null,
            'notes' => isset($validated['notes']) && $validated['notes'] !== '' ? (string) $validated['notes'] : null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ])->save();

        return redirect()->back()->with('success', 'Distributor berhasil diperbarui.');
    }

    public function destroy(Distributor $distributor): RedirectResponse
    {
        $distributor->delete();

        return redirect()->back()->with('success', 'Distributor berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateDistributor(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ]);
    }
}
