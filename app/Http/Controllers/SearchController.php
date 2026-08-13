<?php

namespace App\Http\Controllers;

use App\Services\GlobalSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(private readonly GlobalSearchService $search) {}

    public function __invoke(Request $request): JsonResponse
    {
        $query = $request->string('q')->toString();

        return response()->json([
            'q' => $query,
            'results' => $this->search->search($query, $request->user()),
        ]);
    }
}
