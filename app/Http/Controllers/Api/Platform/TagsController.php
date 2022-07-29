<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Tags\IndexTagsRequest;
use App\Http\Resources\TagResource;
use App\Models\Implementation;
use App\Models\Tag;
use App\Scopes\Builders\FundQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TagsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexTagsRequest $request
     * @return AnonymousResourceCollection
     */
    public function index(IndexTagsRequest $request): AnonymousResourceCollection
    {
        $query = Tag::query();

        if ($request->input('type') === 'funds') {
            $query->where(function(Builder $builder) {
                $builder->where('scope', '=', 'webshop');

                $builder->whereHas('funds', function(Builder $builder) {
                    $fundsQuery = FundQuery::whereActiveFilter(Implementation::activeFundsQuery());
                    $builder->whereIn('funds.id', $fundsQuery->select('funds.id'));
                });
            });
        }

        if ($request->has('scope')) {
            $query->where('scope', '=', $request->input('scope'));
        }

        return TagResource::queryCollection($query, $request);
    }

    /**
     * @param Tag $tag
     * @return TagResource
     */
    public function show(Tag $tag): TagResource
    {
        return TagResource::create($tag);
    }
}
