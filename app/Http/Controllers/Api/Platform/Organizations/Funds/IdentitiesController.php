<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Funds;

use App\Exports\FundIdentitiesExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Funds\Identities\ExportIdentitiesRequest;
use App\Http\Requests\Api\Platform\Organizations\Funds\Identities\IndexIdentitiesRequest;
use App\Http\Requests\Api\Platform\Organizations\Funds\Identities\SendIdentityNotificationRequest;
use App\Http\Resources\Arr\ExportFieldArrResource;
use App\Http\Resources\Sponsor\IdentityBsnResource;
use App\Http\Resources\Sponsor\IdentityResource;
use App\Models\Fund;
use App\Models\Identity;
use App\Models\Organization;
use App\Notifications\Identities\Fund\IdentityRequesterSponsorCustomNotification;
use App\Searches\Sponsor\FundIdentitiesSearch;
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
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        IndexIdentitiesRequest $request,
        Organization $organization,
        Fund $fund
    ): AnonymousResourceCollection|JsonResponse {
        $this->authorize('show', [$organization]);
        $this->authorize('viewIdentitiesSponsor', [$fund, $organization]);

        $isManager = $organization->identityCan($request->identity(), 'manage_vouchers');
        $filters = ['target', 'has_email', 'order_by', 'order_dir', 'with_reservations', $isManager ? 'q' : null];

        $search = new FundIdentitiesSearch($request->only(array_filter($filters)), $fund);
        $query = $isManager ? clone $search->query() : Identity::whereRaw('false');

        $counts = [
            'active' => $fund->activeIdentityQuery()->count(),
            'selected' => (clone $search->query())->count(),
            'without_email' => $fund->activeIdentityQuery(false, false)->count(),
        ];

        $collection = $organization->bsn_enabled
            ? IdentityBsnResource::queryCollection($query)
            : IdentityResource::queryCollection($query);

        return $collection->additional([
            'meta' => compact('counts'),
        ]);
    }

    /**
     * @param Organization $organization
     * @param Fund $fund
     * @param Identity $identity
     * @return IdentityBsnResource|IdentityResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Organization $organization,
        Fund $fund,
        Identity $identity
    ): IdentityBsnResource|IdentityResource {
        $this->authorize('show', [$organization]);
        $this->authorize('showIdentitySponsor', [$fund, $organization, $identity]);

        return $organization->bsn_enabled
            ? IdentityBsnResource::create($identity)
            : IdentityResource::create($identity);
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
        $filters = ['target', 'has_email', 'order_by', 'order_dir', 'with_reservations', 'q'];
        $search = new FundIdentitiesSearch($request->only($filters), $fund);

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
        $filters = ['target', 'has_email', 'order_by', 'order_dir', 'with_reservations', $isManager ? 'q' : null];

        if ($request->input('target') === 'self') {
            $identities = $request->identity()->id;
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
