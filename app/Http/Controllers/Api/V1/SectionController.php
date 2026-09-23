<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SectionResource;
use App\Models\Section;
use Illuminate\Http\JsonResponse;

class SectionController extends Controller
{
    public function index(): JsonResponse
    {
        $sections = Section::query()
            ->where('status', 'activo')
            ->with([
                'module:id,code,name',
            ])
            ->orderBy('module_id')
            ->orderBy('sort_order')
            ->get([
                'id',
                'module_id',
                'code',
                'name',
                'description',
                'status',
                'sort_order',
                'metadata',
            ]);

        return response()->json([
            'data' => SectionResource::collection($sections),
        ]);
    }
}