<?php

namespace App\Http\Controllers\Admin;

use App\Exports\SimpleArrayExport;
use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Reports/Index', [
            'catalog' => $this->reports->catalog(),
        ]);
    }

    public function show(Request $request, string $report): Response
    {
        $data = $this->reports->resolve($report, $request);

        return Inertia::render('Admin/Reports/Show', [
            'report' => $data,
            'filters' => $request->only([
                'location_id',
                'rack_id',
                'category_id',
                'vendor_id',
                'client_id',
                'borrower_user_id',
                'status',
                'movement_type',
                'date_from',
                'date_to',
                'overdue',
            ]),
            'options' => $this->reports->filterOptions(),
            'canExport' => $request->user()?->can('reports.export') ?? false,
        ]);
    }

    public function exportExcel(Request $request, string $report): BinaryFileResponse
    {
        abort_unless($request->user()?->can('reports.export'), 403);

        $data = $this->reports->resolve($report, $request);
        $filename = $data['key'].'-'.now()->format('Ymd-His').'.xlsx';

        return Excel::download(
            new SimpleArrayExport($data['columns'], $data['rows'], $data['title']),
            $filename,
        );
    }

    public function exportPdf(Request $request, string $report): HttpResponse
    {
        abort_unless($request->user()?->can('reports.export'), 403);

        $data = $this->reports->resolve($report, $request);

        $pdf = Pdf::loadView('exports.report-pdf', [
            'title' => $data['title'],
            'columns' => $data['columns'],
            'rows' => $data['rows'],
            'meta' => $data['meta'] ?? [],
            'generatedAt' => now()->timezone(config('app.timezone'))->format('d/m/Y H:i'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download($data['key'].'-'.now()->format('Ymd-His').'.pdf');
    }
}
