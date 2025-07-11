<?php

namespace Tests\Traits;

use App\Models\BusinessType;
use App\Models\Employee;
use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\FundRequest;
use App\Models\FundRequestClarification;
use App\Models\FundRequestRecord;
use App\Models\Identity;
use App\Models\Note;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductReservation;
use App\Models\Traits\HasDbTokens;
use App\Models\Voucher;
use App\Traits\DoesTesting;
use Illuminate\Foundation\Testing\WithFaker;
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
     * @param Organization $organization
     * @param array $query
     * @return TestResponse
     */
    protected function apiGetOrganizationEmailLogsRequest(Organization $organization, array $query): TestResponse
    {
        // assert email log exists
        return $this->getJson(
            "/api/v1/platform/organizations/$organization->id/email-logs?" . http_build_query($query),
            $this->makeApiHeaders($organization->identity),
        );
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
}