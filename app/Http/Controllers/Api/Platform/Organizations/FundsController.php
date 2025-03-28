<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Events\Funds\FundCreatedEvent;
use App\Events\Funds\FundUpdatedEvent;
use App\Exports\FundsDetailedExport;
use App\Exports\FundsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Funds\FinanceOverviewRequest;
use App\Http\Requests\Api\Platform\Organizations\Funds\FinanceRequest;
use App\Http\Requests\Api\Platform\Organizations\Funds\IndexFundRequest;
use App\Http\Requests\Api\Platform\Organizations\Funds\ShowFundRequest;
use App\Http\Requests\Api\Platform\Organizations\Funds\StoreFundCriteriaRequest;
use App\Http\Requests\Api\Platform\Organizations\Funds\StoreFundRequest;
use App\Http\Requests\Api\Platform\Organizations\Funds\UpdateFundBackofficeRequest;
use App\Http\Requests\Api\Platform\Organizations\Funds\UpdateFundCriteriaRequest;
use App\Http\Requests\Api\Platform\Organizations\Funds\UpdateFundRequest;
use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\Arr\ExportFieldArrResource;
use App\Http\Resources\FundResource;
use App\Http\Resources\TopUpResource;
use App\Models\Fund;
use App\Models\Organization;
use App\Scopes\Builders\FundQuery;
use App\Searches\FundSearch;
use App\Statistics\Funds\FinancialOverviewStatistic;
use App\Statistics\Funds\FinancialStatistic;
use Carbon\Carbon;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class FundsController extends Controller
{
    /**
     *  Display a listing of the resource.
     *
     * @param IndexFundRequest $request
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(
        IndexFundRequest $request,
        Organization $organization,
    ): AnonymousResourceCollection {
        $this->authorize('viewAny', [Fund::class, $organization]);

        $query = (new FundSearch($request->only([
            'tag', 'organization_id', 'fund_id', 'fund_ids', 'q', 'implementation_id', 'order_by',
            'order_dir', 'with_archived', 'with_external', 'configured', 'state',
        ]), $organization->funds()->getQuery()))->query();

        if (!$request->isAuthenticated()) {
            $query->where('public', true);
        }

        $meta = [
            'archived_funds_total' => (clone $query)->where('archived', true)->count(),
            'unarchived_funds_total' => (clone $query)->where('archived', false)->count(),
        ];

        if ($request->has('archived')) {
            $query->where('archived', $request->input('archived', false));
        }

        return FundResource::queryCollection(FundQuery::sortByState($query, [
            'active', 'waiting', 'paused', 'closed',
        ]), $request, $request->only('stats', 'year'))->additional(compact('meta'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreFundRequest $request
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException|Throwable
     * @return FundResource
     */
    public function store(StoreFundRequest $request, Organization $organization): FundResource
    {
        $this->authorize('show', $organization);
        $this->authorize('store', [Fund::class, $organization]);

        $auto_requests_validation =
            $request->input('default_validator_employee_id') &&
            $request->input('auto_requests_validation');

        /** @var Fund $fund */
        $fund = $organization->funds()->create(array_merge($request->only([
            'name', 'description', 'description_short', 'description_position', 'start_date', 'end_date',
            'type', 'notification_amount', 'default_validator_employee_id',
            'faq_title', 'request_btn_text', 'external_link_text', 'external_link_url',
            'external_page', 'external_page_url',
        ]), [
            'state' => $organization->initialFundState($request->input('type')),
            'auto_requests_validation' => $auto_requests_validation,
        ]));

        $fund->makeFundConfig($request->only([
            'allow_fund_requests', 'allow_prevalidations', 'allow_direct_requests',
            'email_required', 'contact_info_enabled', 'contact_info_required',
            'contact_info_message_custom', 'contact_info_message_text', 'hide_meta',
            'voucher_amount_visible', 'outcome_type', 'provider_products_required',
            'criteria_label_requirement_show',
        ]));

        $fund->attachMediaByUid($request->input('media_uid'));
        $fund->syncDescriptionMarkdownMedia('cms_media');
        $fund->syncTagsOptional($request->input('tag_ids'));
        $fund->syncFaqOptional($request->input('faq'));

        if (config('forus.features.dashboard.organizations.funds.criteria')) {
            $fund->syncCriteria($request->input('criteria'));
        }

        if (config('forus.features.dashboard.organizations.funds.formula_products') &&
            $request->has('formula_products')) {
            $fund->updateFormulaProducts($request->input('formula_products', []));
        }

        FundCreatedEvent::dispatch($fund);

        return FundResource::create($fund);
    }

    /**
     * @param StoreFundCriteriaRequest $request
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return JsonResponse
     * @noinspection PhpUnused
     */
    public function storeCriteriaValidate(
        StoreFundCriteriaRequest $request,
        Organization $organization
    ): JsonResponse {
        $this->authorize('show', $organization);

        return new JsonResponse([], $request->isAuthenticated() ? 200 : 403);
    }

    /**
     * Display the specified resource.
     *
     * @param ShowFundRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @throws AuthorizationException
     * @return FundResource
     */
    public function show(
        ShowFundRequest $request,
        Organization $organization,
        Fund $fund
    ): FundResource {
        $this->authorize('show', [$fund, $organization]);

        return FundResource::create($fund, $request->only('stats'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateFundRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @throws \Illuminate\Auth\Access\AuthorizationException|Throwable
     * @return FundResource
     */
    public function update(
        UpdateFundRequest $request,
        Organization $organization,
        Fund $fund,
    ): FundResource {
        $this->authorize('show', $organization);

        $manageFund = Gate::allows('update', [$fund, $organization]);
        $manageFundTexts = Gate::allows('updateTexts', [$fund, $organization]);

        if ($manageFundTexts && !$manageFund) {
            $fund->update($request->only([
                'name', 'description', 'description_short', 'description_position', 'request_btn_text',
                'external_link_text', 'external_link_url', 'faq_title', 'external_page', 'external_page_url',
            ]));
        }

        if ($manageFund) {
            $fund->update([
                ...$request->only([
                    'name', 'description', 'description_short', 'description_position', 'request_btn_text',
                    'external_link_text', 'external_link_url', 'faq_title', 'external_page', 'external_page_url',
                    'notification_amount', 'default_validator_employee_id',
                    'auto_requests_validation', 'request_btn_text',
                ]),
                ...($fund->isWaiting() ? $request->only([
                    'start_date', 'end_date',
                ]) : []),
            ]);

            if (!$fund->default_validator_employee_id) {
                $fund->updateModelValue('auto_requests_validation', false);
            }

            $fund->updateFundsConfig([
                ...$request->only([
                    'email_required', 'contact_info_enabled', 'contact_info_required',
                    'contact_info_message_custom', 'contact_info_message_text',
                    'hide_meta', 'voucher_amount_visible', 'provider_products_required',
                    'help_enabled', 'help_title', 'help_block_text', 'help_button_text',
                    'help_email', 'help_phone', 'help_website', 'help_chat', 'help_description',
                    'help_show_email', 'help_show_phone', 'help_show_website', 'help_show_chat',
                    'criteria_label_requirement_show',
                ]),
                ...($fund->organization->allow_payouts ? $request->only([
                    'custom_amount_min', 'custom_amount_max',
                    'allow_preset_amounts', 'allow_preset_amounts_validator',
                    'allow_custom_amounts', 'allow_custom_amounts_validator',
                ]) : []),
            ]);

            if ($fund->organization->allow_payouts && is_array($request->input('amount_presets'))) {
                $fund->syncAmountPresets($request->input('amount_presets'));
            }

            if ($fund->isWaiting()) {
                $fund->updateFundsConfig($request->only([
                    'allow_fund_requests', 'allow_prevalidations', 'allow_direct_requests',
                ]));
            }

            if ($organization->allow_2fa_restrictions) {
                $fund->updateFundsConfig($request->only([
                    'auth_2fa_policy', 'auth_2fa_remember_ip', 'auth_2fa_restrict_emails',
                    'auth_2fa_restrict_auth_sessions', 'auth_2fa_restrict_reimbursements',
                ]));
            }
        }

        if ($manageFund || $manageFundTexts) {
            $fund->attachMediaByUid($request->input('media_uid'));
            $fund->syncDescriptionMarkdownMedia('cms_media');
            $fund->syncTagsOptional($request->input('tag_ids'));
            $fund->syncFaqOptional($request->input('faq'));
        }

        if (Config::get('forus.features.dashboard.organizations.funds.criteria') && $request->has('criteria')) {
            $fund->syncCriteria($request->input('criteria'), !$manageFund);
        }

        if ($manageFund && Config::get('forus.features.dashboard.organizations.funds.formula_products') &&
            $request->has('formula_products')) {
            $fund->updateFormulaProducts($request->input('formula_products', []));
        }

        Event::dispatch(new FundUpdatedEvent($fund));

        return FundResource::create($fund);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateFundCriteriaRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return FundResource
     * @noinspection PhpUnused
     */
    public function updateCriteria(
        UpdateFundCriteriaRequest $request,
        Organization $organization,
        Fund $fund
    ): FundResource {
        $this->authorize('show', $organization);
        $this->authorize('update', [$fund, $organization]);

        if (config('forus.features.dashboard.organizations.funds.criteria')) {
            $fund->syncCriteria($request->input('criteria'));
        }

        return new FundResource($fund);
    }

    /**
     * @param UpdateFundCriteriaRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return JsonResponse
     * @noinspection PhpUnused
     */
    public function updateCriteriaValidate(
        UpdateFundCriteriaRequest $request,
        Organization $organization,
        Fund $fund
    ): JsonResponse {
        $this->authorize('show', $organization);

        return new JsonResponse([], $request->isAuthenticated() && $fund->exists ? 200 : 403);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateFundBackofficeRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return FundResource
     * @noinspection PhpUnused
     */
    public function updateBackoffice(
        UpdateFundBackofficeRequest $request,
        Organization $organization,
        Fund $fund
    ): FundResource {
        $this->authorize('show', $organization);
        $this->authorize('updateBackoffice', [$fund, $organization]);

        $fund->fund_config->update($request->only([
            'backoffice_enabled', 'backoffice_url', 'backoffice_key',
            'backoffice_certificate', 'backoffice_fallback',
            'backoffice_ineligible_policy', 'backoffice_ineligible_redirect_url',
        ]));

        return FundResource::create($fund);
    }

    /**
     * Test fund backoffice connection.
     *
     * @param BaseFormRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return JsonResponse
     * @noinspection PhpUnused
     */
    public function testBackofficeConnection(
        BaseFormRequest $request,
        Organization $organization,
        Fund $fund
    ): JsonResponse {
        $this->authorize('show', $organization);
        $this->authorize('updateBackoffice', [$fund, $organization]);

        $log = $fund->getBackofficeApi(true)->checkStatus();

        return new JsonResponse($log->only([
            'state', 'response_code',
        ]), $request->isAuthenticated() ? 200 : 403);
    }

    /**
     * @param FinanceOverviewRequest $request
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return JsonResponse
     * @noinspection PhpUnused
     */
    public function financesOverview(
        FinanceOverviewRequest $request,
        Organization $organization,
    ): JsonResponse {
        $this->authorize('show', $organization);
        $this->authorize('showFinances', $organization);

        $year = $request->input('year');
        $statistics = new FinancialOverviewStatistic();

        return new JsonResponse($statistics->getStatistics($organization, $year));
    }

    /**
     * @param Organization $organization
     * @param Request $request
     * @return AnonymousResourceCollection
     * @noinspection PhpUnused
     */
    public function getFinancesOverviewExportFields(
        Organization $organization,
        Request $request
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('showFinances', $organization);

        $detailed = $request->input('detailed', false);

        return ExportFieldArrResource::collection(
            $detailed ? FundsDetailedExport::getExportFields() : FundsExport::getExportFields()
        );
    }

    /**
     * Export funds data.
     *
     * @param FinanceOverviewRequest $request
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @noinspection PhpUnused
     */
    public function financesOverviewExport(
        FinanceOverviewRequest $request,
        Organization $organization
    ): BinaryFileResponse {
        $this->authorize('show', $organization);
        $this->authorize('showFinances', $organization);

        $detailed = $request->input('detailed', false);
        $year = $request->input('year');
        $from = Carbon::createFromFormat('Y', $year)->startOfYear();
        $to = $year < now()->year ? Carbon::createFromFormat('Y', $year)->endOfYear() : today();
        $exportType = $request->input('export_type', 'xls');
        $fileName = date('Y-m-d H:i:s') . '.' . $exportType;

        if ($detailed) {
            $fields = $request->input('fields', FundsDetailedExport::getExportFieldsRaw());
            $budgetFunds = $organization->funds()->where('type', Fund::TYPE_BUDGET)->get();
            $exportData = new FundsDetailedExport($budgetFunds, $from, $to, $fields);
        } else {
            $fields = $request->input('fields', FundsExport::getExportFieldsRaw());
            $funds = $organization->funds()->where('state', '!=', Fund::STATE_WAITING)->get();
            $exportData = new FundsExport($funds, $from, $to, $fields);
        }

        return resolve('excel')->download($exportData, $fileName);
    }

    /**
     * @param FinanceRequest $request
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return JsonResponse
     * @noinspection PhpUnused
     */
    public function finances(FinanceRequest $request, Organization $organization): JsonResponse
    {
        $this->authorize('show', $organization);
        $this->authorize('showFinances', $organization);

        $filters = $request->input('filters');
        $financialStatistic = new FinancialStatistic();

        $data = $filters ? $financialStatistic->getFilters($organization, $request->only([
            'type', 'type_value',
        ])) : $financialStatistic->getStatistics($organization, $request->only([
            'type', 'type_value', 'fund_ids', 'postcodes', 'provider_ids',
            'product_category_ids', 'business_type_ids',
        ]));

        return new JsonResponse($data);
    }

    /**
     * @param Organization $organization
     * @param Fund $fund
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return TopUpResource
     * @noinspection PhpUnused
     */
    public function topUp(Organization $organization, Fund $fund): TopUpResource
    {
        $this->authorize('show', $organization);
        $this->authorize('show', [$fund, $organization]);
        $this->authorize('topUp', [$fund, $organization]);

        return TopUpResource::create($fund->getOrCreateTopUp());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Organization $organization
     * @param Fund $fund
     * @throws \Illuminate\Auth\Access\AuthorizationException|Exception
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Organization $organization, Fund $fund): JsonResponse
    {
        $this->authorize('show', $organization);
        $this->authorize('destroy', [$fund, $organization]);

        $fund->fund_formula_products()->delete();
        $fund->delete();

        return new JsonResponse([]);
    }

    /**
     * @param BaseFormRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return FundResource
     */
    public function archive(
        BaseFormRequest $request,
        Organization $organization,
        Fund $fund
    ): FundResource {
        $this->authorize('show', $organization);
        $this->authorize('archive', [$fund, $organization]);

        $fund->archive($organization->findEmployee($request->auth_address()));

        return FundResource::create($fund);
    }

    /**
     * @param BaseFormRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return FundResource
     * @noinspection PhpUnused
     */
    public function unArchive(
        BaseFormRequest $request,
        Organization $organization,
        Fund $fund
    ): FundResource {
        $this->authorize('show', $organization);
        $this->authorize('unarchive', [$fund, $organization]);

        $fund->unArchive($organization->findEmployee($request->auth_address()));

        return FundResource::create($fund);
    }
}
