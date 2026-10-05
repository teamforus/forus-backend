<?php

namespace App\Services\WalletService\Policies;

use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\Identity;
use App\Models\Implementation;
use App\Services\WalletService\Models\WalletDisclosure;
use App\Services\WalletService\Models\WalletFlow;
use App\Services\WalletService\WalletService;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;

class WalletDisclosurePolicy
{
    use HandlesAuthorization;

    /**
     * @param Identity $identity
     * @param Fund $fund
     * @param Implementation $implementation
     * @param string|null $clientType
     * @return bool
     */
    public function store(Identity $identity, Fund $fund, Implementation $implementation, ?string $clientType): bool
    {
        if ($clientType !== Implementation::FRONTEND_WEBSHOP ||
            !Config::get('forus.features.webshop.funds.fund_requests') ||
            $fund->fund_config?->implementation_id !== $implementation->id ||
            !$fund->isConfigured() || !$fund->isActive() || $fund->external ||
            !$fund->fund_config->allow_fund_requests || !$identity->bsn ||
            $fund->identityRequireBsnConfirmation($identity) ||
            !WalletService::disclosureAvailable($implementation)) {
            return false;
        }

        $flow = $fund->fund_config->wallet_disclosure_flow;

        return $flow && $implementation
            ->availableWalletFlowsForProvider(WalletService::PROVIDER_VERID, WalletFlow::TYPE_DISCLOSURE)
            ->contains('id', $flow->id) &&
            Gate::forUser($identity)->allows('createAsRequester', [FundRequest::class, $fund]);
    }

    /**
     * @param Identity $identity
     * @param WalletDisclosure $disclosure
     * @param Fund $fund
     * @param Implementation $implementation
     * @param string|null $clientType
     * @return Response|bool
     */
    public function show(
        Identity $identity,
        WalletDisclosure $disclosure,
        Fund $fund,
        Implementation $implementation,
        ?string $clientType,
    ): Response|bool {
        if ($disclosure->identity_id !== $identity->id || $disclosure->fund_id !== $fund->id ||
            $disclosure->wallet_flow_id !== $fund->fund_config?->wallet_disclosure_flow_id ||
            !$disclosure->isUsable()) {
            return $this->denyAsNotFound();
        }

        return $this->store($identity, $fund, $implementation, $clientType);
    }
}
