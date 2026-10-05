<?php

namespace App\Http\Requests\Api\Platform\Wallets;

use App\Http\Requests\BaseFormRequest;
use App\Models\Implementation;
use App\Services\WalletService\Models\WalletFlow;
use App\Services\WalletService\WalletService;
use Illuminate\Validation\Rule;

class StartWalletDisclosureRequest extends BaseFormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        if (!$this->identity()) {
            $this->deny(trans('requests.wallets.sign_in_first'));
        }

        if ($this->client_type() !== Implementation::FRONTEND_WEBSHOP) {
            $this->deny(trans('requests.wallets.invalid_client_type'));
        }

        if (!WalletService::disclosureAvailable($this->implementation())) {
            $this->deny(trans('requests.wallets.not_enabled'));
        }

        return true;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'flow_id' => ['required', 'integer', Rule::in($this->implementation()
                ->availableWalletFlowsForProvider(WalletService::PROVIDER_VERID, WalletFlow::TYPE_DISCLOSURE)
                ->modelKeys())],
        ];
    }

    /**
     * @return WalletFlow|null
     */
    public function walletFlow(): ?WalletFlow
    {
        return $this->implementation()
            ->availableWalletFlowsForProvider(WalletService::PROVIDER_VERID, WalletFlow::TYPE_DISCLOSURE)
            ->firstWhere('id', (int) $this->input('flow_id'));
    }
}
