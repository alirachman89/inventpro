<?php

namespace App\Http\Controllers;

use App\Models\ApprovalDemo;
use App\Services\ApprovalEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApprovalDemoController extends Controller
{
    public function __construct(private readonly ApprovalEngine $engine) {}

    public function index(Request $request): Response
    {
        $demos = ApprovalDemo::query()
            ->with('creator:id,name')
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (ApprovalDemo $demo) => [
                'id' => $demo->id,
                'title' => $demo->title,
                'amount' => $demo->amount,
                'status' => $demo->status,
                'creator' => $demo->creator?->name,
                'created_at' => $demo->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i'),
            ]);

        return Inertia::render('Approvals/Demo/Index', [
            'demos' => $demos,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'submit_now' => ['nullable', 'boolean'],
        ]);

        $demo = ApprovalDemo::query()->create([
            'title' => $data['title'],
            'amount' => $data['amount'] ?? null,
            'status' => 'draft',
            'created_by' => $request->user()->id,
        ]);

        if ($request->boolean('submit_now')) {
            $this->engine->submit(
                ApprovalDemo::DOCUMENT_TYPE,
                $demo,
                $request->user(),
                $demo->title,
            );

            return redirect()
                ->route('approval-demos.index')
                ->with('success', 'Dokumen demo dibuat dan diajukan untuk approval.');
        }

        return redirect()
            ->route('approval-demos.index')
            ->with('success', 'Dokumen demo berhasil dibuat.');
    }

    public function submit(Request $request, ApprovalDemo $approvalDemo): RedirectResponse
    {
        abort_unless(
            in_array($approvalDemo->status, ['draft', 'rejected'], true),
            422,
            'Dokumen tidak dapat diajukan.'
        );

        $this->engine->submit(
            ApprovalDemo::DOCUMENT_TYPE,
            $approvalDemo,
            $request->user(),
            $approvalDemo->title,
        );

        return redirect()
            ->route('approval-demos.index')
            ->with('success', 'Dokumen demo diajukan untuk approval.');
    }
}
