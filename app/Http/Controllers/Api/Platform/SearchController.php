<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\SearchRequest;
use App\Http\Resources\SearchLiteResource;
use App\Http\Resources\SearchResource;
use App\Searches\WebshopGenericSearch;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SearchController extends Controller
{
    /**
     * @param SearchRequest $request
     * @throws Exception
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function index(SearchRequest $request)
    {
        $overview = $request->get('overview', false);
        $search = new WebshopGenericSearch($request->only([
            'q', 'fund_id', 'fund_type', 'product_category_id', 'organization_id', 'with_external', 'postcode', 'distance',
        ]));

        if (!$overview) {
            return SearchResource::collection($search->query(
                $request->input('search_item_types', ['funds', 'providers', 'products'])
            )->orderBy(
                $request->input('order_by', 'created_at'),
                $request->input('order_dir', 'desc'),
            )->paginate($request->get('per_page', 15)));
        }

        $providers = $search->query('providers');
        $products = $search->query('products');
        $funds = $search->query('funds');

        return response()->json([
            'data' => [
                'products' => [
                    'items' => SearchLiteResource::collection((clone $products)->take(3)->get()),
                    'count' => $products->count(),
                ],
                'funds' => [
                    'items' => SearchLiteResource::collection((clone $funds)->take(3)->get()),
                    'count' => $funds->count(),
                ],
                'providers' => [
                    'items' => SearchLiteResource::collection((clone $providers)->take(3)->get()),
                    'count' => $providers->count(),
                ],
            ],
        ]);
    }
}
