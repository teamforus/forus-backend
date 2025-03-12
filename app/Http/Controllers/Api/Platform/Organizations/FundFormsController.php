<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\FundForms\IndexFundFormRequest;
use App\Http\Resources\FundFormResource;
use App\Models\FundForm;
use App\Models\Organization;
use App\Searches\FundFormSearch;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FundFormsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(IndexFundFormRequest $request, Organization $organization): AnonymousResourceCollection
    {
        $this->authorize('show', $organization);
        $this->authorize('viewAny', [FundForm::class, $organization]);

        $search = new FundFormSearch($request->only([
            'q', 'implementation_id', 'order_by', 'order_dir', 'state', 'per_page',
        ]), FundForm::whereRelation('fund', 'organization_id', $organization->id));

        return FundFormResource::queryCollection($search->query(), $request);
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param FundForm $fundForm
     * @return FundFormResource
     */
    public function show(Organization $organization, FundForm $fundForm): FundFormResource
    {
        $this->authorize('show', $organization);
        $this->authorize('show', [$fundForm, $organization]);

        return FundFormResource::create($fundForm);
    }
}
