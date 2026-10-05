<?php

namespace App\Http\Requests\Api\Platform\Wallets;

use App\Http\Requests\BaseFormRequest;
use App\Models\Implementation;
use App\Services\WalletService\Models\WalletFlow;
use App\Services\WalletService\Models\WalletSession;
use App\Services\WalletService\WalletService;
use Illuminate\Validation\Rule;

class StartWalletAuthRequest extends BaseFormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        $implementation = $this->implementation();
        $clientType = $this->client_type();
        $sessionRequest = $this->input('request') ?: WalletSession::REQUEST_AUTH;

        if (!WalletService::enabled()) {
            $this->deny(trans('requests.wallets.not_enabled'));
        }

        if ($clientType !== Implementation::FRONTEND_WEBSHOP) {
            $this->deny(trans('requests.wallets.invalid_client_type'));
        }

        if (!$implementation) {
            $this->deny(trans('requests.wallets.invalid_implementation'));
        }

        if (!$implementation->walletAvailable()) {
            $this->deny(trans('requests.wallets.not_enabled'));
        }

        if ($sessionRequest === WalletSession::REQUEST_FUND_REQUEST && !$this->isAuthenticated()) {
            $this->deny(trans('requests.wallets.sign_in_first'));
        }

        if ($sessionRequest === WalletSession::REQUEST_AUTH && $this->identity()) {
            $this->deny(trans('requests.wallets.already_authenticated'));
        }

        return true;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        $implementation = $this->implementation();

        $flowIds = $implementation
            ? $implementation->availableWalletFlows()->pluck('id')->all()
            : [];

        return [
            'request' => ['nullable', Rule::in(WalletSession::REQUEST_TYPES)],
            'flow_id' => ['required', 'integer', Rule::in($flowIds)],
            'fund_id' => [
                'nullable',
                'required_if:request,' . WalletSession::REQUEST_FUND_REQUEST,
                Rule::exists('funds', 'id')->whereIn(
                    'id',
                    Implementation::activeFundsQuery()->pluck('id')->toArray()
                ),
            ],
            'target' => 'nullable|string|max:200',
        ];
    }

    /**
     * @return WalletFlow|null
     */
    public function walletFlow(): ?WalletFlow
    {
        $flowId = $this->input('flow_id');

        if (is_numeric($flowId)) {
            return $this->implementation()?->availableWalletFlows()->firstWhere('id', (int) $flowId);
        }

        return null;
    }
}
