<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    public function index(): View
    {
        $staff = User::query()
            ->orderByRaw("CASE role WHEN 'admin' THEN 0 WHEN 'kasir' THEN 1 ELSE 2 END")
            ->orderBy('name')
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'role_label' => $user->role->label(),
                'is_active' => $user->is_active,
                'has_pin' => $user->pin_hash !== null && $user->pin_hash !== '',
                'created_at' => $user->created_at?->toIso8601String(),
            ]);

        return view('admin.staff.index', [
            'staff' => $staff->values()->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'pin' => ['nullable', 'digits:6'],
            'is_active' => ['boolean'],
        ]);

        $pin = isset($validated['pin']) && $validated['pin'] !== '' ? (string) $validated['pin'] : null;

        $created = User::query()->create([
            'name' => (string) $validated['name'],
            'email' => Str::lower((string) $validated['email']),
            'password' => (string) $validated['password'],
            'role' => (string) $validated['role'],
            'pin_hash' => $pin,
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'email_verified_at' => now(),
        ]);

        $this->auditLogService->record(
            action: 'staff.create',
            actorId: $request->user()?->id,
            auditable: $created,
            reason: 'Tambah akun staf baru',
            metadata: [
                'email' => $created->email,
                'role' => $created->role->value,
            ],
        );

        return redirect()->back()->with('success', "Akun {$created->name} berhasil dibuat.");
    }

    public function update(Request $request, User $staff): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($staff->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'is_active' => ['boolean'],
        ]);

        $actor = $request->user();
        $nextRole = (string) $validated['role'];
        $nextActive = (bool) ($validated['is_active'] ?? true);

        if ($actor instanceof User && $actor->id === $staff->id && ($nextRole !== UserRole::Admin->value || ! $nextActive)) {
            return redirect()->back()->with('error', 'Anda tidak dapat menonaktifkan atau mengubah role akun Anda sendiri.');
        }

        $updates = [
            'name' => (string) $validated['name'],
            'email' => Str::lower((string) $validated['email']),
            'role' => $nextRole,
            'is_active' => $nextActive,
        ];

        if (isset($validated['password']) && $validated['password'] !== '') {
            $updates['password'] = (string) $validated['password'];
        }

        $staff->forceFill($updates)->save();

        $this->auditLogService->record(
            action: 'staff.update',
            actorId: $actor?->id,
            auditable: $staff,
            reason: 'Perbarui profil/role staf',
            metadata: [
                'email' => $staff->email,
                'role' => $staff->role->value,
                'is_active' => $staff->is_active,
            ],
        );

        return redirect()->back()->with('success', "Data {$staff->name} berhasil diperbarui.");
    }

    public function updatePin(Request $request, User $staff): RedirectResponse
    {
        $validated = $request->validate([
            'pin' => ['nullable', 'digits:6'],
        ]);

        $pin = isset($validated['pin']) && $validated['pin'] !== '' ? (string) $validated['pin'] : null;

        $staff->forceFill([
            'pin_hash' => $pin,
        ])->save();

        $this->auditLogService->record(
            action: 'staff.pin_updated',
            actorId: $request->user()?->id,
            auditable: $staff,
            reason: $pin !== null ? 'Set / reset PIN otorisasi' : 'Hapus PIN otorisasi',
        );

        return redirect()->back()->with('success', "PIN untuk {$staff->name} berhasil diperbarui.");
    }
}
