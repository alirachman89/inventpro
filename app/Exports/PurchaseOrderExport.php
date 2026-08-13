<?php

namespace App\Exports;

use App\Models\PurchaseOrder;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PurchaseOrderExport implements WithMultipleSheets
{
    public function __construct(private readonly PurchaseOrder $purchaseOrder) {}

    public function sheets(): array
    {
        return [
            new PurchaseOrderHeaderSheet($this->purchaseOrder),
            new PurchaseOrderLinesSheet($this->purchaseOrder),
        ];
    }
}
