<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class DocumentNumberService
{
    public function next(string $settingKey, string $defaultPrefix): string
    {
        $prefix = (string) Setting::getValue($settingKey, $defaultPrefix);
        $period = now()->format('Ym');
        $like = "{$prefix}-{$period}-%";

        return DB::transaction(function () use ($prefix, $period, $like, $settingKey) {
            $table = match ($settingKey) {
                'prefix_po' => 'purchase_orders',
                'prefix_gr' => 'goods_receipts',
                'prefix_movement' => 'stock_movements',
                'prefix_opname' => 'stock_opnames',
                default => throw new \InvalidArgumentException("Prefix key tidak dikenal: {$settingKey}"),
            };

            $last = DB::table($table)
                ->where('number', 'like', $like)
                ->orderByDesc('number')
                ->lockForUpdate()
                ->value('number');

            $seq = 1;
            if ($last && preg_match('/-(\d+)$/', $last, $matches)) {
                $seq = ((int) $matches[1]) + 1;
            }

            return sprintf('%s-%s-%04d', $prefix, $period, $seq);
        });
    }
}
