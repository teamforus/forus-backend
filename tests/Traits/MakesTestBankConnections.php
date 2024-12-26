<?php

namespace Tests\Traits;

use App\Models\BankConnection;
use App\Models\BankConnectionAccount;
use App\Models\Organization;
use App\Services\BankService\Models\Bank;
use App\Traits\DoesTesting;
use Illuminate\Foundation\Testing\WithFaker;

trait MakesTestBankConnections
{
    use WithFaker;
    use DoesTesting;

    /**
     * @param Organization $organization
     * @return BankConnection
     */
    public function makeBankConnection(Organization $organization): BankConnection
    {
        $bank = Bank::forceCreate([
            'key' => 'bng',
            'name' => 'BNG',
            'transaction_cost' => .11,
            'data' => [],
        ]);

        /** @var BankConnection $bank_connections */
        $bank_connections = $organization->bank_connections()->create([
            'bank_id' => $bank->id,
            'implementation_id' => $organization->implementations[0]?->id,
            'redirect_token' => BankConnection::makeUniqueToken('redirect_token', 200),
            'state' => BankConnection::STATE_ACTIVE,
        ]);

        /** @var BankConnectionAccount $account */
        $account = $bank_connections->bank_connection_accounts()->create([
            'monetary_account_id' => 1,
            'monetary_account_iban' => $this->faker->iban(),
            'monetary_account_name' => $this->faker->name(),
        ]);

        $bank_connections->update([
            'bank_connection_account_id' => $account->id,
        ]);

        $organization->refresh();

        return $bank_connections;
    }
}