<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        $categories = Category::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Category $category) => [
                'id' => $category->id,
                'code' => $category->code,
                'name' => $category->name,
                'is_active' => $category->is_active,
                'sort_order' => $category->sort_order,
            ]);

        return Inertia::render('Admin/Categories/Index', [
            'categories' => $categories,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Categories/Form', [
            'category' => null,
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $category = Category::query()->create($request->validated());

        $this->auditLogger->log(
            action: 'created',
            module: 'categories',
            description: "Kategori {$category->code} dibuat",
            auditable: $category,
            newValues: $category->only(['code', 'name', 'is_active', 'sort_order']),
        );

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Kategori berhasil dibuat.');
    }

    public function edit(Category $category): Response
    {
        return Inertia::render('Admin/Categories/Form', [
            'category' => $category->only(['id', 'code', 'name', 'description', 'is_active', 'sort_order']),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $old = $category->only(['code', 'name', 'description', 'is_active', 'sort_order']);
        $category->update($request->validated());

        $this->auditLogger->log(
            action: 'updated',
            module: 'categories',
            description: "Kategori {$category->code} diperbarui",
            auditable: $category,
            oldValues: $old,
            newValues: $category->only(['code', 'name', 'description', 'is_active', 'sort_order']),
        );

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $old = $category->only(['code', 'name', 'is_active']);
        $code = $category->code;
        $category->delete();

        $this->auditLogger->log(
            action: 'deleted',
            module: 'categories',
            description: "Kategori {$code} dihapus",
            oldValues: $old,
        );

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Kategori berhasil dihapus.');
    }
}
