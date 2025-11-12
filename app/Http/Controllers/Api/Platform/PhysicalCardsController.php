<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\PhysicalCards\IndexPhysicalCardsRequest;
use App\Http\Resources\PhysicalCardResource;
use App\Models\PhysicalCard;
use App\Models\Voucher;
use App\Searches\VouchersSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PhysicalCardsController extends Controller
{
    /**
     * @param IndexPhysicalCardsRequest $request
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(IndexPhysicalCardsRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Voucher::class);

        $query = Voucher::query()
            ->where('identity_id', $request->auth_id())
            ->whereDoesntHave('product_reservation');

        $search = new VouchersSearch($request->only([
            'type', 'state', 'archived', 'allow_reimbursements',
            'implementation_id', 'implementation_key', 'product_id',
            'order_by', 'order_dir',
        ]), $query);

        $query = PhysicalCard::query()
            ->whereHas('voucher', fn (Builder $b) => $b->whereIn('id', $search->query()->pluck('id')->toArray()))
            ->orderBy('created_at', $request->post('order_dir', 'desc'));

        return PhysicalCardResource::queryCollection($query, $request, [
            'include_voucher_details' => true,
        ]);
    }
}
