<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Funds;

use App\Exports\FundIdentitiesExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Funds\Identities\ExportIdentitiesRequest;
use App\Http\Requests\Api\Platform\Organizations\Funds\Identities\IndexIdentitiesRequest;
use App\Http\Requests\Api\Platform\Organizations\Funds\Identities\SendIdentityNotificationRequest;
use App\Http\Resources\Arr\ExportFieldArrResource;
use App\Http\Resources\Sponsor\IdentityResource;
use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Identity;
use App\Models\Organization;
use App\Notifications\Identities\Fund\IdentityRequesterSponsorCustomNotification;
use App\Scopes\Builders\FundProviderQuery;
use App\Scopes\Builders\OrganizationQuery;
use App\Searches\Sponsor\FundIdentitiesSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use function JmesPath\search;

class IdentitiesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexIdentitiesRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */

    protected ?Builder $builder;


    public function index(
        IndexIdentitiesRequest $request,
        Organization           $organization,
        Fund                   $fund
    ): AnonymousResourceCollection|JsonResponse
    {
//        if ($request->input("target") === "provider") {
//            $all_providers = FundProvider::where("fund_id", "=", $fund->id)->count();
//            $counts = [
//                'active' => $all_providers,
//                'selected' => 0,
//                'without_email' => 0,
//            ];
//
//        } else {
            $this->authorize('show', [$organization]);
            $this->authorize('showIdentitiesOverview', [$fund, $organization]);

            $isManager = $organization->identityCan($request->identity(), 'manage_vouchers');
            $filters = array_filter(['target', 'has_email', 'order_by', 'order_dir', $isManager ? 'q' : null]);

            $search = new FundIdentitiesSearch($request->only($filters), $fund);
            $query = $isManager ? clone $search->query() : Identity::whereRaw('false');

            $counts = [
                'active' => $fund->activeIdentityQuery()->count(),
                'selected' => (clone $search->query())->count(),
                'without_email' => $fund->activeIdentityQuery(false, false)->count(),
            ];
//        }

        return IdentityResource::queryCollection($query)->additional([
            'meta' => compact('counts'),
        ]);
    }



    /**
     * @param Organization $organization
     * @param Fund $fund
     * @return AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @noinspection PhpUnused
     */
    public function exportFields(
        Organization $organization,
        Fund $fund
    ): AnonymousResourceCollection {
        $this->authorize('show', [$organization]);
        $this->authorize('showIdentities', [$fund, $organization]);

        return ExportFieldArrResource::collection(FundIdentitiesExport::getExportFields());
    }

    /**
     * @param ExportIdentitiesRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @noinspection PhpUnused
     */
    public function export(
        ExportIdentitiesRequest $request,
        Organization $organization,
        Fund $fund
    ): BinaryFileResponse {
        $this->authorize('show', [$organization]);
        $this->authorize('showIdentities', [$fund, $organization]);

        $fields = $request->input('fields', FundIdentitiesExport::getExportFields());
        $search = new FundIdentitiesSearch($request->only([
            'target', 'has_email', 'q', 'order_by', 'order_dir',
        ]), $fund);

        $exportType = $request->input('export_type', 'csv');
        $exportData = new FundIdentitiesExport($search->get(), $fields);
        $exportFileName = date('Y-m-d H:i:s') . '.' . $exportType;

        return resolve('excel')->download($exportData, $exportFileName);
    }

    /**
     * Display a listing of the resource.
     *
     * @param SendIdentityNotificationRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @noinspection PhpUnused
     */
    public function sendIdentityNotification(
        SendIdentityNotificationRequest $request,
        Organization $organization,
        Fund $fund
    ): AnonymousResourceCollection|JsonResponse {

        $this->authorize('show', [$organization]);
        $this->authorize('sendIdentityNotifications', [$fund, $organization]);

        $isManager = $organization->identityCan($request->identity(), 'manage_vouchers');

        if ($request->input('target') === 'self') {
            $identities = $request->identity()->id;

        }elseif ($request->input('target') === 'all-providers') {
            $providers = FundProvider::whereFundId($fund->id)->get();
            $providers->load("organization.identity");
            $identities = $providers->pluck('organization.identity.id')->toArray();

        } elseif ($request->input('target') === 'confirmed-providers') {
            $providers = FundProvider::with("organization.identity")->where(function (Builder $builder) use ($fund) {
                FundProviderQuery::whereApprovedForFundsFilter($builder, $fund->id);
            })->get();
            $identities = $providers->pluck("organization.identity.id")->toArray();

        } elseif ($request->input('target') === 'declined-providers') {
            $declinedProviders = FundProviderQuery::whereDeclinedForFundsFilter($fund->id);
            $identities = $declinedProviders->load("organization.identity")->pluck("organization.identity.id")->toArray();

        } else {
            $filters = ['target', 'has_email', 'order_by', 'order_dir', $isManager ? 'q' : null];
            $search = new FundIdentitiesSearch(array_filter($request->only($filters)), $fund);
            $identities = $search->query()->pluck('id')->toArray();

        }

        $eventLog = $fund->log($fund::EVENT_SPONSOR_NOTIFICATION_CREATED, [
            'fund' => $fund,
            'sponsor' => $organization,
        ], [
            'notification_subject' => $request->input('subject'),
            'notification_content' => $request->input('content'),
            'notification_target_identities' => (array) $identities,
        ]);

        IdentityRequesterSponsorCustomNotification::send($eventLog);

        return new JsonResponse();
    }
}
