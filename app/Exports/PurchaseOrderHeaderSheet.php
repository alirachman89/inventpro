<?php

namespace App\Exports;

use App\Models\PurchaseOrder;
use App\Models\Setting;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class PurchaseOrderHeaderSheet implements FromArray, ShouldAutoSize, WithTitle
{
    public function __construct(private readonly PurchaseOrder $purchaseOrder) {}

    public function title(): string
    {
        return 'Header';
    }

    public function array(): array
    {
        $po = $this->purchaseOrder;
        $company = Setting::getValue('company_name', 'InventPro');

        return [
            ['Perusahaan', $company],
            ['Nomor PO', $po->number],
            ['Status', $po->status],
            ['Tanggal PO', optional($po->order_date)->format('Y-m-d')],
            ['Estimasi datang', optional($po->expected_date)->format('Y-m-d')],
            ['Vendor kode', $po->vendor?->code],
            ['Vendor nama', $po->vendor?->name],
            ['Vendor kontak', $po->vendor?->contact_person],
            ['Vendor telepon', $po->vendor?->phone],
            ['Vendor email', $po->vendor?->email],
            ['Vendor alamat', $po->vendor?->address],
            ['Total', (float) $po->total_amount],
            ['Dibuat oleh', $po->creator?->name],
            ['Catatan', $po->notes],
            ['Diekspor pada', now()->timezone(config('app.timezone'))->format('Y-m-d H:i')],
        ];
    }
}
