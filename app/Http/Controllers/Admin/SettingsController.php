<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\KioskDevice;
use App\Services\AuditLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SettingsController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    public function index(): View
    {
        $devices = KioskDevice::query()
            ->with('pairedBy:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (KioskDevice $device): array => [
                'id' => $device->id,
                'name' => $device->name,
                'device_token' => $device->device_token,
                'pair_url' => route('kiosk.pair', ['token' => $device->device_token]),
                'is_active' => $device->is_active,
                'last_seen_at' => $device->last_seen_at?->toIso8601String(),
                'paired_by_name' => $device->pairedBy?->name,
            ]);

        return view('admin.settings.index', [
            'settings' => [
                'cafe_name' => AppSetting::getValue('cafe_name', 'Our Coffee') ?? 'Our Coffee',
                'cafe_address' => AppSetting::getValue('cafe_address', '') ?? '',
                'cafe_phone' => AppSetting::getValue('cafe_phone', '') ?? '',
                'receipt_footer' => AppSetting::getValue('receipt_footer', '') ?? '',
                'kiosk_idle_timeout_seconds' => (int) (AppSetting::getValue('kiosk_idle_timeout_seconds', '60') ?? '60'),
                'kiosk_idle_warning_seconds' => (int) (AppSetting::getValue('kiosk_idle_warning_seconds', '50') ?? '50'),
                'kiosk_confirmation_reset_seconds' => (int) (AppSetting::getValue('kiosk_confirmation_reset_seconds', '8') ?? '8'),
                'qris_expiry_seconds' => (int) (AppSetting::getValue('qris_expiry_seconds', '300') ?? '300'),
            ],
            'devices' => $devices->values()->all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cafe_name' => ['required', 'string', 'max:100'],
            'cafe_address' => ['nullable', 'string', 'max:255'],
            'cafe_phone' => ['nullable', 'string', 'max:50'],
            'receipt_footer' => ['nullable', 'string', 'max:255'],
            'kiosk_idle_timeout_seconds' => ['required', 'integer', 'min:30', 'max:600'],
            'kiosk_idle_warning_seconds' => ['required', 'integer', 'min:10', 'lt:kiosk_idle_timeout_seconds'],
            'kiosk_confirmation_reset_seconds' => ['required', 'integer', 'min:3', 'max:60'],
            'qris_expiry_seconds' => ['required', 'integer', 'min:60', 'max:900'],
        ]);

        $userId = $request->user()?->id;

        foreach ($validated as $key => $value) {
            AppSetting::setValue($key, $value !== null ? (string) $value : '', $userId);
        }

        $this->auditLogService->record(
            action: 'settings.update',
            actorId: $userId,
            reason: 'Perbarui pengaturan kafe & Kiosk',
        );

        return redirect()->back()->with('success', 'Pengaturan kafe dan Kiosk berhasil disimpan.');
    }

    public function storeDevice(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $device = KioskDevice::query()->create([
            'name' => (string) $validated['name'],
            'device_token' => Str::random(40),
            'is_active' => true,
            'paired_by' => $request->user()?->id,
        ]);

        $this->auditLogService->record(
            action: 'kiosk_device.create',
            actorId: $request->user()?->id,
            auditable: $device,
            reason: 'Tambah perangkat Kiosk baru',
        );

        return redirect()->back()->with('success', "Perangkat Kiosk {$device->name} berhasil dibuat.");
    }

    public function toggleDevice(Request $request, KioskDevice $kioskDevice): RedirectResponse
    {
        $kioskDevice->forceFill([
            'is_active' => ! $kioskDevice->is_active,
        ])->save();

        $this->auditLogService->record(
            action: 'kiosk_device.toggle',
            actorId: $request->user()?->id,
            auditable: $kioskDevice,
            reason: $kioskDevice->is_active ? 'Aktifkan perangkat Kiosk' : 'Nonaktifkan perangkat Kiosk',
        );

        return redirect()->back()->with('success', "Status perangkat {$kioskDevice->name} diperbarui.");
    }

    public function rotateDeviceToken(Request $request, KioskDevice $kioskDevice): RedirectResponse
    {
        $kioskDevice->forceFill([
            'device_token' => Str::random(40),
            'paired_by' => $request->user()?->id,
        ])->save();

        $this->auditLogService->record(
            action: 'kiosk_device.rotate_token',
            actorId: $request->user()?->id,
            auditable: $kioskDevice,
            reason: 'Rotasi token pairing Kiosk',
        );

        return redirect()->back()->with('success', "Token pairing {$kioskDevice->name} berhasil dirotasi.");
    }

    public function destroyDevice(Request $request, KioskDevice $kioskDevice): RedirectResponse
    {
        $name = $kioskDevice->name;
        $this->auditLogService->record(
            action: 'kiosk_device.delete',
            actorId: $request->user()?->id,
            auditable: $kioskDevice,
            reason: "Hapus perangkat Kiosk {$name}",
        );

        $kioskDevice->delete();

        return redirect()->back()->with('success', "Perangkat Kiosk {$name} berhasil dihapus.");
    }
}
