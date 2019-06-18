<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Funds;

use App\Http\Resources\BunqMeIdealRequestResource;
use App\Models\BunqMeTab;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BunqTransactionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(
        Request $request
    ) {
        return BunqMeIdealRequestResource::collection(
            BunqMeTab::query()->where('status', 'PAID')->paginate(
                $request->input('per_page', 20)
            )
        );
    }
}
