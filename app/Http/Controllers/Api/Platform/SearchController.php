<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\SearchRequest;
use App\Http\Resources\SearchResource;
use App\Models\Fund;
use App\Models\Implementation;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\Paginator;

class SearchController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function index(SearchRequest $request)
    {
        $overview = $request->get('overview');

        $products = Product::search($request)->select([
            'id', 'name', 'description', 'created_at', 'price', 'organization_id'
        ])->selectRaw('"product" as item_type')->with(['organization', 'photo']);

        $funds = Fund::search($request, Fund::query())->selectRaw(
            'id, name, description, created_at, NULL as price, organization_id, "fund" as item_type'
        )->with(['organization', 'logo']);

        $providers = Implementation::searchProviders($request)->selectRaw(
            'id, name, description, created_at, NULL as price, id as organization_id, "provider" as item_type'
        )->with(['logo']);

        if ($overview) {
            $typesArr = $request->get('search_item_types');
            $types = ['products', 'providers', 'funds'];
            if ($typesArr && $typesArr[0]) {
                $query = null;
                foreach ($request->get('search_item_types') as $key => $type) {
                    $key == 0 && in_array($type, $types) ?
                        $query = $$type :
                        $query->union($$type);
                }
            } else {
                $query = $products->union($funds->getQuery())->union($providers);
            }

            return $query ?
                SearchResource::collection($query->paginate($request->get('per_page', 15))) :
                response()->json(['data' => []]);
        }

        return response()->json([
            'data' => [
                'products' => $products->take(3)->get(),
                'funds' => $funds->take(3)->get(),
                'providers' => $providers->take(3)->get(),
            ]
        ]);
    }
}
