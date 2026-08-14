<?php

namespace Database\Seeders;

use App\Models\ApprovalDemo;
use App\Models\ApprovalWorkflow;
use Illuminate\Database\Seeder;

class ApprovalWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        $workflows = [
            [
                'document_type' => ApprovalDemo::DOCUMENT_TYPE,
                'name' => 'Demo Approval',
                'steps' => [
                    ['name' => 'Persetujuan Approver', 'approver_role' => 'approver', 'mode' => 'any'],
                ],
            ],
            [
                'document_type' => 'purchase_order',
                'name' => 'Purchase Order',
                'steps' => [
                    ['name' => 'Persetujuan Purchasing Lead', 'approver_role' => 'approver', 'mode' => 'any'],
                ],
            ],
            [
                'document_type' => 'stock_opname',
                'name' => 'Opname Barang',
                'steps' => [
                    ['name' => 'Persetujuan Adjustment', 'approver_role' => 'approver', 'mode' => 'any'],
                ],
            ],
            [
                'document_type' => 'borrow_request',
                'name' => 'Peminjaman Barang',
                'steps' => [
                    ['name' => 'Persetujuan Peminjaman', 'approver_role' => 'approver', 'mode' => 'any'],
                ],
            ],
            [
                'document_type' => 'asset_dispose',
                'name' => 'Asset Dispose / Lost',
                'steps' => [
                    ['name' => 'Persetujuan Manager', 'approver_role' => 'approver', 'mode' => 'any'],
                    ['name' => 'Persetujuan Admin', 'approver_role' => 'admin', 'mode' => 'any'],
                ],
            ],
        ];

        foreach ($workflows as $data) {
            $workflow = ApprovalWorkflow::query()->updateOrCreate(
                ['document_type' => $data['document_type']],
                [
                    'name' => $data['name'],
                    'is_active' => true,
                ],
            );

            $workflow->steps()->delete();

            foreach ($data['steps'] as $index => $step) {
                $workflow->steps()->create([
                    'step_order' => $index + 1,
                    'name' => $step['name'],
                    'approver_role' => $step['approver_role'],
                    'mode' => $step['mode'],
                ]);
            }
        }
    }
}
