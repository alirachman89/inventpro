<?php

namespace App\Exports;

use App\Models\PurchaseOrder;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class PurchaseOrderLinesSheet implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    public function __construct(private readonly PurchaseOrder $purchaseOrder) {}

    public function title(): string
    {
        return 'Baris';
    }

    public function headings(): array
    {
        return [
            'No',
            'SKU',
            'Nama item',
            'UOM',
            'Qty ordered',
            'Qty received',
            'Qty sisa',
            'Harga satuan',
            'Subtotal',
            'Catatan',
        ];
    }

    public function array(): array
    {
        return $this->purchaseOrder->lines->map(function ($line, $index) {
            return [
                $index + 1,
                $line->item?->sku,
                $line->item?->name,
                $line->item?->uom?->code,
                (float) $line->qty_ordered,
                (float) $line->qty_received,
                $line->qtyOutstanding(),
                (float) $line->unit_price,
                (float) $line->line_total,
                $line->notes,
            ];
        })->all();
    }
}
