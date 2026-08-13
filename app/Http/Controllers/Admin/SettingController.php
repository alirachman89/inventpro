<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingRequest;
use App\Models\Setting;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function edit(): Response
    {
        $values = Setting::allCached();

        return Inertia::render('Admin/Settings/Form', [
            'settings' => [
                'company_name' => $values['company_name'] ?? 'InventPro',
                'company_address' => $values['company_address'] ?? '',
                'company_phone' => $values['company_phone'] ?? '',
                'timezone' => $values['timezone'] ?? 'Asia/Jakarta',
                'prefix_po' => $values['prefix_po'] ?? 'PO',
                'prefix_gr' => $values['prefix_gr'] ?? 'GR',
                'prefix_opname' => $values['prefix_opname'] ?? 'OPN',
                'prefix_borrow' => $values['prefix_borrow'] ?? 'BRW',
                'prefix_movement' => $values['prefix_movement'] ?? 'MOV',
            ],
            'timezones' => [
                'Asia/Jakarta',
                'Asia/Makassar',
                'Asia/Jayapura',
                'UTC',
            ],
        ]);
    }

    public function update(UpdateSettingRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $old = Setting::allCached();

        Setting::setMany($data, 'company');

        $this->auditLogger->log(
            action: 'updated',
            module: 'settings',
            description: 'Pengaturan perusahaan diperbarui',
            oldValues: collect($data)->mapWithKeys(fn ($_, $key) => [$key => $old[$key] ?? null])->all(),
            newValues: $data,
        );

        return redirect()
            ->route('admin.settings.edit')
            ->with('success', 'Pengaturan berhasil disimpan.');
    }
}
