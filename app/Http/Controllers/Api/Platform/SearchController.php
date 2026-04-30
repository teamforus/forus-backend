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
    public function index(SearchRequest $request): JsonResponse|AnonymousResourceCollection
    {
        $overview = $request->get('overview', false);
        $search = new WebshopGenericSearch($request->only([
            'q', 'fund_id', 'product_category_id', 'organization_id', 'with_external', 'postcode', 'distance',
            'order_by', 'order_dir',
        ]));

        if (!$overview) {
            return SearchResource::queryCollection($search->query($request->input('search_item_types', [
                'funds', 'providers', 'products',
            ])), $request);
        }

        $providers = $search->query('providers');
        $products = $search->query('products');
        $funds = $search->query('funds');

        return new JsonResponse([
            'data' => [
                'products' => [
                    'items' => SearchLiteResource::createCollection((clone $products)->take(3)->get()),
                    'count' => $products->count(),
                ],
                'funds' => [
                    'items' => SearchLiteResource::createCollection((clone $funds)->take(3)->get()),
                    'count' => $funds->count(),
                ],
                'providers' => [
                    'items' => SearchLiteResource::createCollection((clone $providers)->take(3)->get()),
                    'count' => $providers->count(),
                ],
            ],
        ]);
    }
}
