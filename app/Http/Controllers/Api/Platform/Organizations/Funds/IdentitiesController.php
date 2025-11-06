<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Funds;

use App\Exports\FundIdentitiesExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Funds\Identities\ExportIdentitiesRequest;
use App\Http\Requests\Api\Platform\Organizations\Funds\Identities\IndexIdentitiesRequest;
use App\Http\Requests\Api\Platform\Organizations\Funds\Identities\SendIdentityNotificationRequest;
use App\Http\Resources\Arr\ExportFieldArrResource;
use App\Http\Resources\Sponsor\SponsorIdentityResource;
use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\Permission;
use App\Notifications\Identities\Fund\IdentityRequesterSponsorCustomNotification;
use App\Scopes\Builders\FundProviderQuery;
use App\Searches\Sponsor\FundIdentitiesSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class IdentitiesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexIdentitiesRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|JsonResponse
     */
    public function index(
        IndexIdentitiesRequest $request,
        Organization $organization,
        Fund $fund,
    ): AnonymousResourceCollection|JsonResponse {
        $this->authorize('show', [$organization]);
        $this->authorize('viewIdentitiesSponsor', [$fund, $organization]);

        $isManager = $organization->identityCan($request->identity(), Permission::MANAGE_VOUCHERS);
        $filters = ['target', 'has_email', 'order_by', 'order_dir', 'with_reservations', $isManager ? 'q' : null];

        $search = new FundIdentitiesSearch($request->only(array_filter($filters)), $fund);
        $query = $isManager ? clone $search->query() : Identity::whereRaw('false');

        $counts = [
            'active' => $fund->activeIdentityQuery()->count(),
            'selected' => (clone $search->query())->count(),
            'without_email' => $fund->activeIdentityQuery(false, false)->count(),
        ];

        return SponsorIdentityResource::queryCollection($query)->additional([
            'meta' => compact('counts'),
            'detailed' => false,
            'organization' => $organization,
        ]);
    }

    /**
     * @param Organization $organization
     * @param Fund $fund
     * @param Identity $identity
     * @return SponsorIdentityResource
     */
    public function show(
        Organization $organization,
        Fund $fund,
        Identity $identity
    ): SponsorIdentityResource {
        $this->authorize('show', [$organization]);
        $this->authorize('showIdentitySponsor', [$fund, $organization, $identity]);

        return SponsorIdentityResource::create($identity, [
            'detailed' => false,
            'organization' => $organization,
        ]);
    }

    /**
     * @param Organization $organization
     * @param Fund $fund
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return AnonymousResourceCollection
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
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @noinspection PhpUnused
     */
    public function export(
        ExportIdentitiesRequest $request,
        Organization $organization,
        Fund $fund
    ): BinaryFileResponse {
        $this->authorize('show', [$organization]);
        $this->authorize('showIdentities', [$fund, $organization]);

        $fields = $request->input('fields', FundIdentitiesExport::getExportFieldsRaw());
        $filters = ['target', 'has_email', 'order_by', 'order_dir', 'with_reservations', 'q'];
        $search = new FundIdentitiesSearch($request->only($filters), $fund);

        $exportType = $request->input('data_format', 'csv');
        $exportData = new FundIdentitiesExport($search->query(), $fields);
        $exportFileName = date('Y-m-d H:i:s') . '.' . $exportType;

        return resolve('excel')->download($exportData, $exportFileName);
    }

    /**
     * Display a listing of the resource.
     *
     * @param SendIdentityNotificationRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|JsonResponse
     * @noinspection PhpUnused
     */
    public function sendIdentityNotification(
        SendIdentityNotificationRequest $request,
        Organization $organization,
        Fund $fund
    ): AnonymousResourceCollection|JsonResponse {
        $this->authorize('show', [$organization]);
        $this->authorize('sendIdentityNotifications', [$fund, $organization]);

        $isManager = $organization->identityCan($request->identity(), Permission::MANAGE_VOUCHERS);
        $filters = ['target', 'has_email', 'order_by', 'order_dir', 'with_reservations', $isManager ? 'q' : null];

        if ($request->input('target') === 'self') {
            $identities = $request->identity()->id;
        } elseif ($request->input('target') === 'providers_all') {
            $providers = FundProvider::whereFundId($fund->id)->get();
            $providers->load('organization.identity');
            $identities = $providers->pluck('organization.identity.id')->toArray();
        } elseif ($request->input('target') === 'providers_approved') {
            $providers = FundProvider::with('organization.identity')->where(function (Builder $builder) use ($fund) {
                FundProviderQuery::whereApprovedForFundsFilter($builder, $fund->id);
            })->get();
            $identities = $providers->pluck('organization.identity.id')->toArray();
        } elseif ($request->input('target') === 'providers_rejected') {
            $declinedProviders = FundProvider::with('organization.identity')->where(function (Builder $builder) use ($fund) {
                FundProviderQuery::whereDeclinedForFundsFilter($builder, $fund->id);
            })->get();
            $identities = $declinedProviders->pluck('organization.identity.id')->toArray();
        } else {
            $search = new FundIdentitiesSearch($request->only(array_filter($filters)), $fund);
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
