<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\BaseFormRequest;
use App\Models\DecisionTree;
use Illuminate\Http\JsonResponse;

class DecisionTreeController extends Controller
{
    /**
     * @param BaseFormRequest $request
     * @return JsonResponse
     */
    public function index(BaseFormRequest $request): JsonResponse
    {
        return response()->json(DecisionTree::build($request->implementation()));
    }
}
