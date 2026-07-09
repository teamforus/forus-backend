<?php

namespace Tests\Traits;

use App\Models\BusinessType;
use App\Models\Employee;
use App\Models\Fund;
use App\Models\FundProductLimit;
use App\Models\FundProvider;
use App\Models\FundRequest;
use App\Models\FundRequestClarification;
use App\Models\FundRequestRecord;
use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Note;
use App\Models\Organization;
use App\Models\PrevalidationRequest;
use App\Models\PrevalidationRequestRecord;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductReservation;
use App\Models\ReservationField;
use App\Models\Traits\HasDbTokens;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Services\FileService\Models\File;
use App\Traits\DoesTesting;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;

trait MakesApiRequests
{
    use WithFaker;
    use HasDbTokens;
    use DoesTesting;
    use MakesTestProducts;
    use TestsReservations;

    /**
     * @param array $data
     * @param Identity $identity
     * @return TestResponse
     */
    public function apiMakeOrganizationRequest(array $data, Identity $identity): TestResponse
    {
        return $this->postJson('/api/v1/platform/organizations', $data, $this->makeApiHeaders($identity));
    }

    /**
     * @param array $data
     * @param Identity $identity
     * @return Organization
     */
    public function apiMakeOrganization(array $data, Identity $identity): Organization
    {
        $response = $this
            ->apiMakeOrganizationRequest([
                'name' => $this->faker->text(16),
                'iban' => $this->faker->iban('NL'),
                'email' => $this->makeUniqueEmail(),
                'phone' => '1234567890',
                'kvk' => '00000000',
                'business_type_id' => BusinessType::inRandomOrder()->first()->id,
                ...$data,
            ], $identity)
            ->assertSuccessful();

        return $identity->organizations()->findOrFail($response->json('data.id'));
    }

    /**
     * @param Implementation $implementation
     * @param array $data
     * @param Identity $identity
     * @return TestResponse
     */
    public function apiUpdateImplementationAuthPageRequest(
        Implementation $implementation,
        array $data,
        Identity $identity,
    ): TestResponse {
        return $this->patchJson(
            sprintf(
                '/api/v1/platform/organizations/%s/implementations/%s/auth-page',
                $implementation->organization_id,
                $implementation->id,
            ),
            $data,
            $this->makeApiHeaders($identity),
        );
    }

    /**
     * @param Organization $organization
     * @param array $data
     * @param Identity $identity
     * @return TestResponse
     */
    public function apiMakeFundRequest(Organization $organization, array $data, Identity $identity): TestResponse
    {
        return $this->postJson(
            "/api/v1/platform/organizations/$organization->id/funds",
            $data,
            $this->makeApiHeaders($identity),
        );
    }

    /**
     * @param Organization $organization
     * @param array $data
     * @param Identity $identity
     * @return Fund
     */
    public function apiMakeFund(Organization $organization, array $data, Identity $identity): Fund
    {
        // workaround for start date having to be at least 5 days from now
        $now = now();
        $this->travelTo($now->copy()->subDays(6));

        $response = $this
            ->apiMakeFundRequest($organization, [
                'name' => $this->faker->text(16),
                'outcome_type' => 'voucher',
                'start_date' => $now->copy()->format('Y-m-d'),
                'end_date' => $now->copy()->addYear()->format('Y-m-d'),
                ...$data,
            ], $identity)
            ->assertSuccessful();

        $this->travelBack();

        return $organization->funds()->findOrFail($response->json('data.id'));
    }

    /**
     * @param Organization $organization
     * @param array $query
     * @param Identity $identity
     * @return TestResponse
     */
    public function apiGetFundProductLimitsRequest(
        Organization $organization,
        array $query,
        Identity $identity,
    ): TestResponse {
        $queryString = $query ? '?' . http_build_query($query) : '';

        return $this->getJson(
            "/api/v1/platform/organizations/$organization->id/fund-product-limits$queryString",
            $this->makeApiHeaders($identity),
        );
    }

    /**
     * @param Organization $organization
     * @param FundProductLimit $fundProductLimit
     * @param Identity $identity
     * @return TestResponse
     */
    public function apiGetFundProductLimitRequest(
        Organization $organization,
        FundProductLimit $fundProductLimit,
        Identity $identity,
    ): TestResponse {
        return $this->getJson(
            "/api/v1/platform/organizations/$organization->id/fund-product-limits/$fundProductLimit->id",
            $this->makeApiHeaders($identity),
        );
    }

    /**
     * @param Organization $organization
     * @param array $data
     * @param Identity $identity
     * @return TestResponse
     */
    public function apiMakeFundProductLimitRequest(
        Organization $organization,
        array $data,
        Identity $identity,
    ): TestResponse {
        return $this->postJson(
            "/api/v1/platform/organizations/$organization->id/fund-product-limits",
            $data,
            $this->makeApiHeaders($identity),
        );
    }

    /**
     * @param Organization $organization
     * @param FundProductLimit $fundProductLimit
     * @param array $data
     * @param Identity $identity
     * @return TestResponse
     */
    public function apiUpdateFundProductLimitRequest(
        Organization $organization,
        FundProductLimit $fundProductLimit,
        array $data,
        Identity $identity,
    ): TestResponse {
        return $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/fund-product-limits/$fundProductLimit->id",
            $data,
            $this->makeApiHeaders($identity),
        );
    }

    /**
     * @param Organization $organization
     * @param FundProductLimit $fundProductLimit
     * @param Identity $identity
     * @return TestResponse
     */
    public function apiDeleteFundProductLimitRequest(
        Organization $organization,
        FundProductLimit $fundProductLimit,
        Identity $identity,
    ): TestResponse {
        return $this->deleteJson(
            "/api/v1/platform/organizations/$organization->id/fund-product-limits/$fundProductLimit->id",
            [],
            $this->makeApiHeaders($identity),
        );
    }

    /**
     * @param Organization $organization
     * @param array $data
     * @param Identity $identity
     * @return TestResponse
     */
    public function apiMakeProductRequest(Organization $organization, array $data, Identity $identity): TestResponse
    {
        return $this->postJson(
            "/api/v1/platform/organizations/$organization->id/products",
            $data,
            $this->makeApiHeaders($identity),
        );
    }

    /**
     * @param Organization $organization
     * @param array $data
     * @param Identity $identity
     * @return Product
     */
    public function apiMakeProduct(Organization $organization, array $data, Identity $identity): Product
    {
        $response = $this
            ->apiMakeProductRequest($organization, [
                'name' => $this->faker->text(16),
                'description' => $this->faker->text(512),
                'price_type' => 'regular',
                'price' => '10',
                'product_category_id' => ProductCategory::inRandomOrder()->first()->id,
                'total_amount' => 10,
                ...$data,
            ], $identity)
            ->assertSuccessful();

        return $organization->products()->findOrFail($response->json('data.id'));
    }

    /**
     * @param Organization $organization
     * @param Product $product
     * @param Identity $identity
     * @return TestResponse
     */
    public function apiDeleteProductRequest(Organization $organization, Product $product, Identity $identity): TestResponse
    {
        return $this->deleteJson(
            "/api/v1/platform/organizations/$organization->id/products/$product->id",
            [],
            $this->makeApiHeaders($identity),
        );
    }

    /**
     * @param Organization $organization
     * @param Product $product
     * @param Identity $identity
     * @return TestResponse
     */
    public function apiDeleteProduct(Organization $organization, Product $product, Identity $identity): TestResponse
    {
        return $this
            ->apiDeleteProductRequest($organization, $product, $identity)
            ->assertSuccessful();
    }

    /**
     * @param Organization $organization
     * @param Fund $fund
     * @param Identity $identity
     * @return TestResponse
     */
    public function apiApplyProviderToFundRequest(Organization $organization, Fund $fund, Identity $identity): TestResponse
    {
        return $this->postJson(
            "/api/v1/platform/organizations/$organization->id/provider/funds",
            ['fund_id' => $fund->id],
            $this->makeApiHeaders($identity),
        );
    }

    /**
     * @param Organization $organization
     * @param Fund $fund
     * @param Identity $identity
     * @return FundProvider
     */
    public function apiApplyProviderToFund(Organization $organization, Fund $fund, Identity $identity): FundProvider
    {
        $response = $this
            ->apiApplyProviderToFundRequest($organization, $fund, $identity)
            ->assertSuccessful();

        return $organization->fund_providers()->findOrFail($response->json('data.id'));
    }

    /**
     * @param Organization $organization
     * @param Fund $fund
     * @param array $data
     * @param Identity $identity
     * @return TestResponse
     */
    public function apiMakeVoucherAsSponsorRequest(Organization $organization, Fund $fund, array $data, Identity $identity): TestResponse
    {
        return $this->postJson(
            "/api/v1/platform/organizations/$organization->id/sponsor/vouchers",
            ['fund_id' => $fund->id, 'amount' => '1000', ...$data],
            $this->makeApiHeaders($identity),
        );
    }

    /**
     * @param Organization $organization
     * @param Fund $fund
     * @param array $data
     * @param Identity $identity
     * @return Voucher
     */
    public function apiMakeVoucherAsSponsor(Organization $organization, Fund $fund, array $data, Identity $identity): Voucher
    {
        $response = $this
            ->apiMakeVoucherAsSponsorRequest($organization, $fund, $data, $identity)
            ->assertSuccessful();

        return Voucher::where('id', $response->json('data.id'))->firstOrFail();
    }

    /**
     * @param Organization $organization
     * @param FundProvider $fundProvider
     * @param array $data
     * @param Identity $identity
     * @return TestResponse
     */
    public function apiUpdateFundProviderRequest(
        Organization $organization,
        FundProvider $fundProvider,
        array $data,
        Identity $identity
    ): TestResponse {
        return $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/funds/$fundProvider->fund_id/providers/$fundProvider->id",
            $data,
            $this->makeApiHeaders($identity),
        );
    }

    /**
     * @param Organization $organization
     * @param FundProvider $fundProvider
     * @param array $data
     * @param Identity $identity
     * @return FundProvider
     */
    public function apiUpdateFundProvider(
        Organization $organization,
        FundProvider $fundProvider,
        array $data,
        Identity $identity
    ): FundProvider {
        $response = $this
            ->apiUpdateFundProviderRequest($organization, $fundProvider, $data, $identity)
            ->assertSuccessful();

        return FundProvider::findOrFail($response->json('data.id'));
    }

    /**
     * @param Voucher $voucher
     * @param Identity $identity
     * @return TestResponse
     */
    public function apiMeAppVoucherAsProviderRequest(
        Voucher $voucher,
        Identity $identity
    ): TestResponse {
        return $this->getJson(
            '/api/v1/platform/provider/vouchers/' . $voucher->token_without_confirmation->address,
            $this->makeApiHeaders($identity),
        );
    }

    /**
     * @param Voucher $voucher
     * @param Identity $identity
     * @return FundProvider
     */
    public function apiMeAppVoucherAsProvider(
        Voucher $voucher,
        Identity $identity
    ): FundProvider {
        $response = $this
            ->apiMeAppVoucherAsProviderRequest($voucher, $identity)
            ->assertSuccessful();

        return FundProvider::findOrFail($response->json('data.id'));
    }

    /**
     * @param Voucher $voucher
     * @param Identity $identity
     * @return TestResponse
     */
    public function apiMeAppVoucherProductsAsProviderRequest(
        Voucher $voucher,
        Identity $identity
    ): TestResponse {
        return $this->getJson(
            '/api/v1/platform/provider/vouchers/' . $voucher->token_without_confirmation->address . '/products',
            $this->makeApiHeaders($identity),
        );
    }

    /**
     * @param Voucher $voucher
     * @param Identity $identity
     * @return FundProvider
     */
    public function apiMeAppVoucherProductsAsProvider(
        Voucher $voucher,
        Identity $identity
    ): FundProvider {
        $response = $this
            ->apiMeAppVoucherProductsAsProviderRequest($voucher, $identity)
            ->assertSuccessful();

        return FundProvider::findOrFail($response->json('data.id'));
    }

    /**
     * @param Voucher $voucher
     * @param Organization $organization
     * @param Identity $identity
     * @return TestResponse
     */
    public function apiMeAppVoucherProductVouchersAsProviderRequest(
        Voucher $voucher,
        Organization $organization,
        Identity $identity
    ): TestResponse {
        $address = $voucher->token_without_confirmation->address;

        return $this->getJson(
            "/api/v1/platform/provider/vouchers/$address/product-vouchers?organization_id=$organization->id",
            $this->makeApiHeaders($identity),
        );
    }

    /**
     * @param Voucher $voucher
     * @param Organization $organization
     * @param Identity $identity
     * @return FundProvider
     */
    public function apiMeAppVoucherProductVouchersAsProvider(
        Voucher $voucher,
        Organization $organization,
        Identity $identity
    ): FundProvider {
        $response = $this
            ->apiMeAppVoucherProductVouchersAsProviderRequest($voucher, $organization, $identity)
            ->assertSuccessful();

        return FundProvider::findOrFail($response->json('data.id'));
    }

    /**
     * @param array $data
     * @param Identity $identity
     * @return TestResponse
     */
    public function apiMakeProductReservationRequest(
        array $data,
        Identity $identity,
    ): TestResponse {
        return $this->postJson(
            '/api/v1/platform/product-reservations',
            $data,
            $this->makeApiHeaders($identity),
        );
    }

    /**
     * @param array $data
     * @param Identity $identity
     * @return ProductReservation
     */
    public function apiMakeProductReservation(
        array $data,
        Identity $identity
    ): ProductReservation {
        $response = $this
            ->apiMakeProductReservationRequest($data, $identity)
            ->assertSuccessful();

        return ProductReservation::findOrFail($response->json('data.id'));
    }

    /**
     * @param ProductReservation $reservation
     * @param Identity $identity
     * @return TestResponse
     */
    public function apiCancelProductReservationRequest(
        ProductReservation $reservation,
        Identity $identity,
    ): TestResponse {
        return $this->postJson(
            "/api/v1/platform/product-reservations/$reservation->id/cancel",
            [],
            $this->makeApiHeaders($identity),
        );
    }

    /**
     * @param ProductReservation $reservation
     * @param Identity $identity
     * @return ProductReservation
     */
    public function apiCancelProductReservation(
        ProductReservation $reservation,
        Identity $identity,
    ): ProductReservation {
        $response = $this
            ->apiCancelProductReservationRequest($reservation, $identity)
            ->assertSuccessful();

        return ProductReservation::findOrFail($response->json('data.id'));
    }

    /**
     * @param ProductReservation $reservation
     * @param Identity $identity
     * @param array $data
     * @return TestResponse
     */
    public function apiCancelProductReservationByProviderRequest(
        ProductReservation $reservation,
        Identity $identity,
        array $data = [],
    ): TestResponse {
        return $this->postJson(
            "/api/v1/platform/organizations/{$reservation->product->organization->id}/product-reservations/$reservation->id/reject",
            $data,
            $this->makeApiHeaders($identity),
        );
    }

    /**
     * @param ProductReservation $reservation
     * @param Identity $identity
     * @param array $data
     * @return ProductReservation
     */
    public function apiCancelReservationByProvider(
        ProductReservation $reservation,
        Identity $identity,
        array $data = [],
    ): ProductReservation {
        $response = $this
            ->apiCancelProductReservationByProviderRequest($reservation, $identity, $data)
            ->assertSuccessful();

        return ProductReservation::findOrFail($response->json('data.id'));
    }

    /**
     * @param Organization $organization
     * @param ProductReservation $reservation
     * @param ReservationField $field
     * @param array $data
     * @return TestResponse
     */
    public function apiUpdateProductReservationFieldByProviderRequest(
        Organization $organization,
        ProductReservation $reservation,
        ReservationField $field,
        array $data = [],
    ): TestResponse {
        return $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/product-reservations/$reservation->id/fields/$field->id",
            $data,
            $this->makeApiHeaders($this->makeIdentityProxy($organization->identity)),
        );
    }

    /**
     * @param Organization $organization
     * @param ProductReservation $reservation
     * @param ReservationField $field
     * @param array $data
     * @return ProductReservation
     */
    public function apiUpdateProductReservationFieldByProvider(
        Organization $organization,
        ProductReservation $reservation,
        ReservationField $field,
        array $data = [],
    ): ProductReservation {
        $response = $this
            ->apiUpdateProductReservationFieldByProviderRequest($organization, $reservation, $field, $data)
            ->assertSuccessful();

        return ProductReservation::findOrFail($response->json('data.id'));
    }

    /**
     * @param Organization $organization
     * @param array $query
     * @return TestResponse
     */
    public function apiGetProductReservationsByProviderRequest(
        Organization $organization,
        array $query = [],
    ): TestResponse {
        $queryString = $query ? '?' . http_build_query($query) : '';

        return $this->getJson(
            "/api/v1/platform/organizations/$organization->id/product-reservations$queryString",
            $this->makeApiHeaders($this->makeIdentityProxy($organization->identity)),
        );
    }

    /**
     * @param Organization $organization
     * @param Fund $fund
     * @param string|null $primaryKey
     * @return TestResponse
     */
    public function apiMakePrevalidationForTestCriteriaRequest(
        Organization $organization,
        Fund $fund,
        ?string $primaryKey = null,
    ): TestResponse {
        return $this->apiMakeStorePrevalidationRequest($organization, $fund, [
            $this->makeRequestCriterionValue($fund, 'test_bool', 'Ja'),
            $this->makeRequestCriterionValue($fund, 'test_iban', fake()->iban()),
            $this->makeRequestCriterionValue($fund, 'test_date', '01-01-2010'),
            $this->makeRequestCriterionValue($fund, 'test_email', fake()->email()),
            $this->makeRequestCriterionValue($fund, 'test_string', 'lorem_ipsum'),
            $this->makeRequestCriterionValue($fund, 'test_string_any', 'ipsum_lorem'),
            $this->makeRequestCriterionValue($fund, 'test_number', 7),
            $this->makeRequestCriterionValue($fund, 'test_select', 'foo'),
            $this->makeRequestCriterionValue($fund, 'test_select_number', 2),
        ], [
            $fund->fund_config->csv_primary_key => $primaryKey ?: token_generator()->generate(32),
        ]);
    }

    /**
     * @param Organization $organization
     * @param array $data
     * @param Identity $identity
     * @return Employee|null
     */
    protected function apiMakeEmployee(Organization $organization, array $data, Identity $identity): ?Employee
    {
        $response = $this
            ->apiMakeEmployeeRequest($organization, $data, $identity)
            ->assertSuccessful();

        return $organization->employees()->findOrFail($response->json('data.id'));
    }

    /**
     * @param Organization $organization
     * @param array $data
     * @param Identity $identity
     * @return TestResponse
     */
    protected function apiMakeEmployeeRequest(Organization $organization, array $data, Identity $identity): TestResponse
    {
        return $this->postJson(
            "/api/v1/platform/organizations/$organization->id/employees",
            $data,
            $this->makeApiHeaders($identity)
        );
    }

    /**
     * @param Identity $identity
     * @param Fund $fund
     * @param array $data
     * @param bool $validate
     * @param array $headers
     * @return TestResponse
     */
    protected function apiMakeFundRequestRequest(
        Identity $identity,
        Fund $fund,
        array $data,
        bool $validate,
        array $headers = []
    ): TestResponse {
        return $this->postJson(
            "/api/v1/platform/funds/$fund->id/requests" . ($validate ? '/validate' : ''),
            $data,
            $this->makeApiHeaders($this->makeIdentityProxy($identity), $headers),
        );
    }

    /**
     * @param FundRequest $fundRequest
     * @param Employee $employee
     * @param array $data
     * @return TestResponse
     */
    protected function apiFundRequestApproveRequest(
        FundRequest $fundRequest,
        Employee $employee,
        array $data = [],
    ): TestResponse {
        $organization = $fundRequest->fund->organization;

        return $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/approve",
            $data,
            $this->makeApiHeaders($employee->identity),
        );
    }

    /**
     * @param FundRequest $fundRequest
     * @param array $data
     * @param Employee $employee
     * @return TestResponse
     */
    protected function apiFundRequestDisregardRequest(
        FundRequest $fundRequest,
        array $data,
        Employee $employee,
    ): TestResponse {
        $organization = $fundRequest->fund->organization;

        return $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/disregard",
            $data,
            $this->makeApiHeaders($employee->identity),
        );
    }

    /**
     * @param Organization $organization
     * @param Employee $employee
     * @param array $query
     * @return TestResponse
     */
    protected function apiGetFundRequestsRequest(
        Organization $organization,
        Employee $employee,
        array $query,
    ): TestResponse {
        return $this->getJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests?" . http_build_query($query),
            $this->makeApiHeaders($employee->identity),
        );
    }

    /**
     * @param Organization $organization
     * @param Employee $employee
     * @param FundRequest $fundRequest
     * @return TestResponse
     */
    protected function apiGetFundRequestRequest(
        Organization $organization,
        Employee $employee,
        FundRequest $fundRequest,
    ): TestResponse {
        return $this->getJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id",
            $this->makeApiHeaders($employee->identity),
        );
    }

    /**
     * @param Organization $organization
     * @param Employee $employee
     * @param FundRequest $fundRequest
     * @return TestResponse
     */
    protected function apiGetFundRequestNotesRequest(
        Organization $organization,
        Employee $employee,
        FundRequest $fundRequest,
    ): TestResponse {
        return $this->getJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/notes",
            $this->makeApiHeaders($employee->identity),
        );
    }

    /**
     * @param Organization $organization
     * @param Employee $employee
     * @param FundRequest $fundRequest
     * @param array $data
     * @return TestResponse
     */
    protected function apiMakeFundRequestNoteRequest(
        Organization $organization,
        Employee $employee,
        FundRequest $fundRequest,
        array $data,
    ): TestResponse {
        return $this->postJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/notes",
            $data,
            $this->makeApiHeaders($employee->identity),
        );
    }

    /**
     * @param Organization $organization
     * @param Employee $employee
     * @param FundRequest $fundRequest
     * @param Note $note
     * @return TestResponse
     */
    protected function apiDeleteFundRequestNoteRequest(
        Organization $organization,
        Employee $employee,
        FundRequest $fundRequest,
        Note $note,
    ): TestResponse {
        return $this->deleteJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/notes/$note->id",
            [],
            $this->makeApiHeaders($employee->identity),
        );
    }

    /**
     * @param Organization $organization
     * @param Employee $employee
     * @param FundRequest $fundRequest
     * @param Note $note
     * @return TestResponse
     */
    protected function apiGetFundRequestNoteRequest(
        Organization $organization,
        Employee $employee,
        FundRequest $fundRequest,
        Note $note,
    ): TestResponse {
        return $this->getJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/note/$note->id",
            $this->makeApiHeaders($employee->identity),
        );
    }

    /**
     * @param FundRequest $fundRequest
     * @param array $data
     * @param Employee $employee
     * @return TestResponse
     */
    protected function apiFundRequestDeclineRequest(
        FundRequest $fundRequest,
        array $data,
        Employee $employee,
    ): TestResponse {
        $organization = $fundRequest->fund->organization;

        return $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/decline",
            $data,
            $this->makeApiHeaders($employee->identity),
        );
    }

    /**
     * @param FundRequest $fundRequest
     * @param Employee $employee
     * @return TestResponse
     */
    protected function apiFundRequestAssignRequest(
        FundRequest $fundRequest,
        Employee $employee,
    ): TestResponse {
        $organization = $fundRequest->fund->organization;

        return $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/assign",
            [],
            $this->makeApiHeaders($employee->identity),
        );
    }

    /**
     * @param FundRequest $fundRequest
     * @param Employee $employee
     * @return TestResponse
     */
    protected function apiFundRequestResignRequest(
        FundRequest $fundRequest,
        Employee $employee,
    ): TestResponse {
        $organization = $fundRequest->fund->organization;

        return $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/resign",
            [],
            $this->makeApiHeaders($employee->identity),
        );
    }

    /**
     * @param FundRequest $fundRequest
     * @param Employee $employee
     * @param array $data
     * @return TestResponse
     */
    protected function apiFundRequestAssignEmployeeRequest(
        FundRequest $fundRequest,
        Employee $employee,
        array $data = [],
    ): TestResponse {
        $organization = $fundRequest->fund->organization;

        return $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/assign-employee",
            $data,
            $this->makeApiHeaders($employee->identity),
        );
    }

    /**
     * @param FundRequest $fundRequest
     * @param Employee $employee
     * @param array $data
     * @return TestResponse
     */
    protected function apiFundRequestResignEmployeeRequest(
        FundRequest $fundRequest,
        Employee $employee,
        array $data,
    ): TestResponse {
        $organization = $fundRequest->fund->organization;

        return $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/resign-employee",
            $data,
            $this->makeApiHeaders($employee->identity),
        );
    }

    /**
     * @param FundRequest $fundRequest
     * @param Employee $employee
     * @param array $data
     * @return TestResponse
     */
    protected function apiMakeFundRequestClarificationRequest(
        FundRequest $fundRequest,
        Employee $employee,
        array $data,
    ): TestResponse {
        $organization = $fundRequest->fund->organization;

        return $this->postJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/clarifications",
            $data,
            $this->makeApiHeaders($employee->identity),
        );
    }

    /**
     * @param FundRequestClarification $clarification
     * @param Identity $identity
     * @param array $data
     * @return TestResponse
     */
    protected function apiRespondFundRequestClarificationRequest(
        FundRequestClarification $clarification,
        Identity $identity,
        array $data = []
    ): TestResponse {
        $fundRequestRecord = $clarification->fund_request_record;

        return $this->patchJson(
            "/api/v1/platform/fund-requests/$fundRequestRecord->fund_request_id/clarifications/$clarification->id",
            $data,
            $this->makeApiHeaders($identity)
        );
    }

    /**
     * @param FundRequest $fundRequest
     * @param array $data
     * @param Employee $employee
     * @return TestResponse
     */
    protected function apiMakeFundRequestRecordRequest(
        FundRequest $fundRequest,
        array $data,
        Employee $employee,
    ): TestResponse {
        $organization = $fundRequest->fund->organization;

        return $this->postJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/records",
            $data,
            $this->makeApiHeaders($employee->identity),
        );
    }

    /**
     * @param FundRequestRecord $fundRequestRecord
     * @param array $data
     * @param Employee $employee
     * @return TestResponse
     */
    protected function apiUpdateFundRequestRecordRequest(
        FundRequestRecord $fundRequestRecord,
        array $data,
        Employee $employee,
    ): TestResponse {
        $organization = $fundRequestRecord->fund_request->fund->organization;

        return $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequestRecord->fund_request_id/records/$fundRequestRecord->id",
            $data,
            $this->makeApiHeaders($employee->identity),
        );
    }

    /**
     * @param Identity $identity
     * @param File $file
     * @return TestResponse
     */
    protected function apiGetFileRequest(Identity $identity, File $file): TestResponse
    {
        return $this->getJson("/api/v1/files/$file->uid", $this->makeApiHeaders($identity));
    }

    /**
     * @param Identity $identity
     * @param File $file
     * @return TestResponse
     */
    protected function apiDownloadFileRequest(Identity $identity, File $file): TestResponse
    {
        return $this->getJson("/api/v1/files/$file->uid/download", $this->makeApiHeaders($identity));
    }

    /**
     * @param Identity $identity
     * @param File $file
     * @return TestResponse
     */
    protected function apiDownloadFileArchiveRequest(Identity $identity, File $file): TestResponse
    {
        return $this->getJson("/api/v1/files/$file->uid/download-archive", $this->makeApiHeaders($identity));
    }

    /**
     * @param Identity $identity
     * @param File $file
     * @return TestResponse
     */
    protected function apiDownloadFilePreviewArchiveRequest(Identity $identity, File $file): TestResponse
    {
        return $this->getJson("/api/v1/files/$file->uid/download-preview-archive", $this->makeApiHeaders($identity));
    }

    /**
     * @param Identity $identity
     * @param array $data
     * @return TestResponse
     */
    protected function apiUploadFileRequest(
        Identity $identity,
        array $data,
    ): TestResponse {
        return $this->postJson('/api/v1/files', $data, $this->makeApiHeaders($identity));
    }

    /**
     * @param Identity $identity
     * @param array $data
     * @return TestResponse
     */
    protected function apiUploadProductReservationCustomFieldFileRequest(
        Identity $identity,
        array $data = [],
    ): TestResponse {
        return $this->apiUploadFileRequest($identity, [
            'file' => UploadedFile::fake()->image('reservation-custom-field-file.png'),
            'type' => 'product_reservation_custom_field',
            ...$data,
        ]);
    }

    /**
     * @param Identity $identity
     * @param array $data
     * @return File
     */
    protected function apiUploadProductReservationCustomFieldFile(
        Identity $identity,
        array $data = [],
    ): File {
        $response = $this
            ->apiUploadProductReservationCustomFieldFileRequest($identity, $data)
            ->assertSuccessful();

        return File::where('uid', $response->json('data.uid'))->firstOrFail();
    }

    /**
     * @param Organization $organization
     * @param array $query
     * @param array $headers
     * @return TestResponse
     */
    protected function apiGetOrganizationEmailLogsRequest(
        Organization $organization,
        array $query,
        array $headers = [],
    ): TestResponse {
        return $this->getJson(
            "/api/v1/platform/organizations/$organization->id/email-logs?" . http_build_query($query),
            $this->makeApiHeaders($organization->identity, $headers),
        );
    }

    /**
     * @param Organization $organization
     * @param Fund $fund
     * @param array $records
     * @param array $extraData
     * @return TestResponse
     */
    protected function apiMakeStorePrevalidationRequest(
        Organization $organization,
        Fund $fund,
        array $records,
        array $extraData = [],
    ): TestResponse {
        $proxy = $this->makeIdentityProxy($organization->identity);
        $criteria = $fund->criteria()->pluck('record_type_key', 'id')->toArray();

        return $this->postJson("/api/v1/platform/organizations/$organization->id/prevalidations", [
            'fund_id' => $fund->id,
            'data' => [
                ...array_reduce($records, fn ($list, $record) => [
                    ...$list,
                    $criteria[$record['fund_criterion_id']] => $record['value'],
                ], []),
                ...$extraData,
            ],
        ], $this->makeApiHeaders($proxy));
    }

    /**
     * @param Organization $organization
     * @param Fund $fund
     * @param array $data
     * @param array $overwrite
     * @return TestResponse
     */
    protected function makeStorePrevalidationBatchRequest(
        Organization $organization,
        Fund $fund,
        array $data,
        array $overwrite = [],
    ): TestResponse {
        return $this->postJson("/api/v1/platform/organizations/$organization->id/prevalidations/collection", [
            'fund_id' => $fund->id,
            'data' => $data,
            'overwrite' => $overwrite,
        ], $this->makeApiHeaders($this->makeIdentityProxy($organization->identity)));
    }

    /**
     * @param Organization $organization
     * @param array $data
     * @param Identity|null $identity
     * @return TestResponse
     */
    protected function apiMakePrevalidationRequestCollectionRequest(
        Organization $organization,
        array $data,
        ?Identity $identity = null,
    ): TestResponse {
        return $this->postJson(
            "/api/v1/platform/organizations/$organization->id/prevalidation-requests/collection",
            $data,
            $this->makeApiHeaders($identity ?: $this->makeIdentityProxy($organization->identity)),
        );
    }

    /**
     * @param Organization $organization
     * @param Identity|null $identity
     * @return TestResponse
     */
    protected function apiGetPrevalidationRequestsRequest(
        Organization $organization,
        ?Identity $identity = null,
    ): TestResponse {
        return $this->getJson(
            "/api/v1/platform/organizations/$organization->id/prevalidation-requests",
            $this->makeApiHeaders($identity ?: $this->makeIdentityProxy($organization->identity)),
        );
    }

    /**
     * @param Organization $organization
     * @param PrevalidationRequest $prevalidationRequest
     * @param Identity|null $identity
     * @return TestResponse
     */
    protected function apiGetPrevalidationRequestRequest(
        Organization $organization,
        PrevalidationRequest $prevalidationRequest,
        ?Identity $identity = null,
    ): TestResponse {
        return $this->getJson(
            "/api/v1/platform/organizations/$organization->id/prevalidation-requests/$prevalidationRequest->id",
            $this->makeApiHeaders($identity ?: $this->makeIdentityProxy($organization->identity)),
        );
    }

    /**
     * @param Organization $organization
     * @param PrevalidationRequest $prevalidationRequest
     * @param Identity|null $identity
     * @return TestResponse
     */
    protected function apiGetPrevalidationRequestPersonRequest(
        Organization $organization,
        PrevalidationRequest $prevalidationRequest,
        ?Identity $identity = null,
    ): TestResponse {
        return $this->getJson(
            "/api/v1/platform/organizations/$organization->id/prevalidation-requests/$prevalidationRequest->id/person",
            $this->makeApiHeaders($identity ?: $this->makeIdentityProxy($organization->identity)),
        );
    }

    /**
     * @param Organization $organization
     * @param PrevalidationRequest $prevalidationRequest
     * @param Identity|null $identity
     * @return TestResponse
     */
    protected function apiGetPrevalidationRequestNotesRequest(
        Organization $organization,
        PrevalidationRequest $prevalidationRequest,
        ?Identity $identity = null,
    ): TestResponse {
        return $this->getJson(
            "/api/v1/platform/organizations/$organization->id/prevalidation-requests/$prevalidationRequest->id/notes",
            $this->makeApiHeaders($identity ?: $this->makeIdentityProxy($organization->identity)),
        );
    }

    /**
     * @param Organization $organization
     * @param PrevalidationRequest $prevalidationRequest
     * @param Identity|null $identity
     * @return TestResponse
     */
    protected function apiPrevalidationRequestResubmitRequest(
        Organization $organization,
        PrevalidationRequest $prevalidationRequest,
        ?Identity $identity = null,
    ): TestResponse {
        return $this->getJson(
            "/api/v1/platform/organizations/$organization->id/prevalidation-requests/$prevalidationRequest->id/resubmit",
            $this->makeApiHeaders($identity ?: $this->makeIdentityProxy($organization->identity)),
        );
    }

    /**
     * @param Organization $organization
     * @param Identity|null $identity
     * @return TestResponse
     */
    protected function apiPrevalidationRequestResubmitFailedRequest(
        Organization $organization,
        ?Identity $identity = null,
    ): TestResponse {
        return $this->getJson(
            "/api/v1/platform/organizations/$organization->id/prevalidation-requests/resubmit-failed",
            $this->makeApiHeaders($identity ?: $this->makeIdentityProxy($organization->identity)),
        );
    }

    /**
     * @param Organization $organization
     * @param PrevalidationRequest $prevalidationRequest
     * @param Identity|null $identity
     * @return TestResponse
     */
    protected function apiPrevalidationRequestDeleteRequest(
        Organization $organization,
        PrevalidationRequest $prevalidationRequest,
        ?Identity $identity = null,
    ): TestResponse {
        return $this->deleteJson(
            "/api/v1/platform/organizations/$organization->id/prevalidation-requests/$prevalidationRequest->id",
            [],
            $this->makeApiHeaders($identity ?: $this->makeIdentityProxy($organization->identity)),
        );
    }

    /**
     * @param Organization $organization
     * @param PrevalidationRequest $prevalidationRequest
     * @param array $data
     * @param Identity|null $identity
     * @return TestResponse
     */
    protected function apiPrevalidationRequestApproveMissedRecordsRequest(
        Organization $organization,
        PrevalidationRequest $prevalidationRequest,
        array $data = [],
        ?Identity $identity = null,
    ): TestResponse {
        return $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/prevalidation-requests/$prevalidationRequest->id/approve-missed-records",
            $data,
            $this->makeApiHeaders($identity ?: $this->makeIdentityProxy($organization->identity)),
        );
    }

    /**
     * @param Organization $organization
     * @param PrevalidationRequest $prevalidationRequest
     * @param Identity|null $identity
     * @return TestResponse
     */
    protected function apiPrevalidationRequestFinalizeRequest(
        Organization $organization,
        PrevalidationRequest $prevalidationRequest,
        ?Identity $identity = null,
    ): TestResponse {
        return $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/prevalidation-requests/$prevalidationRequest->id/finalize",
            [],
            $this->makeApiHeaders($identity ?: $this->makeIdentityProxy($organization->identity)),
        );
    }

    /**
     * @param Organization $organization
     * @param PrevalidationRequest $prevalidationRequest
     * @param array $data
     * @param Identity|null $identity
     * @return TestResponse
     */
    protected function apiStorePrevalidationRequestNoteRequest(
        Organization $organization,
        PrevalidationRequest $prevalidationRequest,
        array $data,
        ?Identity $identity = null,
    ): TestResponse {
        return $this->postJson(
            "/api/v1/platform/organizations/$organization->id/prevalidation-requests/$prevalidationRequest->id/notes",
            $data,
            $this->makeApiHeaders($identity ?: $this->makeIdentityProxy($organization->identity)),
        );
    }

    /**
     * @param Organization $organization
     * @param PrevalidationRequest $prevalidationRequest
     * @param Note $note
     * @param Identity|null $identity
     * @return TestResponse
     */
    protected function apiDeletePrevalidationRequestNoteRequest(
        Organization $organization,
        PrevalidationRequest $prevalidationRequest,
        Note $note,
        ?Identity $identity = null,
    ): TestResponse {
        return $this->deleteJson(
            "/api/v1/platform/organizations/$organization->id/prevalidation-requests/$prevalidationRequest->id/notes/$note->id",
            [],
            $this->makeApiHeaders($identity ?: $this->makeIdentityProxy($organization->identity)),
        );
    }

    /**
     * @param Organization $organization
     * @param PrevalidationRequest $prevalidationRequest
     * @param PrevalidationRequestRecord $record
     * @param array $data
     * @param Identity|null $identity
     * @return TestResponse
     */
    protected function apiUpdatePrevalidationRequestRecordRequest(
        Organization $organization,
        PrevalidationRequest $prevalidationRequest,
        PrevalidationRequestRecord $record,
        array $data,
        ?Identity $identity = null,
    ): TestResponse {
        return $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/prevalidation-requests/$prevalidationRequest->id/records/$record->id",
            $data,
            $this->makeApiHeaders($identity ?: $this->makeIdentityProxy($organization->identity)),
        );
    }

    /**
     * @param array $data
     * @param Identity $identity
     * @return TestResponse
     */
    protected function apiMakePayoutRequest(array $data, Identity $identity): TestResponse
    {
        return $this->postJson('/api/v1/platform/payouts', $data, $this->makeApiHeaders($identity));
    }

    /**
     * @param array $data
     * @param Identity $identity
     * @return VoucherTransaction
     */
    protected function apiMakePayout(array $data, Identity $identity): VoucherTransaction
    {
        $response = $this->apiMakePayoutRequest($data, $identity)->assertSuccessful();

        return VoucherTransaction::findOrFail($response->json('data.id'));
    }

    /**
     * @param Identity $identity
     * @param Voucher $voucher
     * @return TestResponse
     */
    protected function apiGetVoucherRequest(Identity $identity, Voucher $voucher): TestResponse
    {
        return $this->getJson("/api/v1/platform/vouchers/$voucher->number", $this->makeApiHeaders($identity));
    }

    /**
     * @param Organization $organization
     * @param array $query
     * @return TestResponse
     */
    protected function apiGetSponsorPayoutBankAccountsRequest(Organization $organization, array $query = []): TestResponse
    {
        $queryString = $query ? '?' . http_build_query($query) : '';

        return $this->getJson(
            "/api/v1/platform/organizations/$organization->id/sponsor/payouts/bank-accounts$queryString",
            $this->makeApiHeaders($this->makeIdentityProxy($organization->identity)),
        );
    }

    /**
     * @param Voucher $voucher
     * @param Organization $providerOrganization
     * @param array $data
     * @param Identity $identity
     * @return TestResponse
     */
    protected function makeProviderVoucherTransactionRequest(
        Voucher $voucher,
        Organization $providerOrganization,
        array $data,
        Identity $identity,
    ): TestResponse {
        $manualVoucherToken = $voucher->token_without_confirmation->address;

        return $this->postJson("/api/v1/platform/provider/vouchers/$manualVoucherToken/transactions", [
            'organization_id' => $providerOrganization->id,
            ...$data,
        ], $this->makeApiHeaders($identity));
    }

    /**
     * @param Voucher $voucher
     * @param Organization $providerOrganization
     * @param array $data
     * @param Identity $identity
     * @return VoucherTransaction
     * @noinspection PhpUnused
     */
    protected function makeProviderVoucherTransaction(
        Voucher $voucher,
        Organization $providerOrganization,
        array $data,
        Identity $identity,
    ): VoucherTransaction {
        $response = $this->makeProviderVoucherTransactionRequest($voucher, $providerOrganization, $data, $identity)
            ->assertSuccessful();

        return VoucherTransaction::findOrFail($response->json('data.id'));
    }

    /**
     * @param Organization $organization
     * @param Product $product
     * @param array $params
     * @return TestResponse
     */
    protected function makeProductUpdateRequest(
        Organization $organization,
        Product $product,
        array $params
    ): TestResponse {
        return $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/products/$product->id",
            $params,
            $this->makeApiHeaders($organization->identity),
        );
    }
}
