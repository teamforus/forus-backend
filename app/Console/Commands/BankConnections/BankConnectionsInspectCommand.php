<?php

namespace App\Console\Commands\BankConnections;

use App\Console\Commands\BaseCommand;
use App\Models\Organization;
use App\Services\BankService\Values\BankPayment;
use Illuminate\Database\Eloquent\Builder;

class BankConnectionsInspectCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bank-connections:inspect';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Helper tool for bank connections inspection.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->printHeader("BNG inspection", 2);
        $this->showMainMenu();
    }

    /**
     * @return void
     */
    protected function showMainMenu(): void
    {
        do {
            $this->printHeader("Select action:");
            $this->printList([
                '[1] Inspect bank connections (' . $this->getOrganizationsQuery()->count() . ' organizations)',
                null,
                '[q] Quit',
            ]);

            $value = $this->ask("Select action:", 1);

            if ($value == 1) {
                $this->selectOrganizationCommand();
            }

            $this->handleGlobalCommands($value);
            $this->printInvalidCommand($value);
        } while (true);
    }

    /**
     * @return void
     */
    protected function selectOrganizationCommand(): void
    {
        $organizations = $this->getOrganizationsQuery()->get();

        if ($organizations->isEmpty()) {
            $this->printText('No organizations with active bank connection found.');
            $this->printSeparator();
            $this->showMainMenu();
            return;
        }

        do {
            $this->printHeader("Select target organization:");
            $this->printList($organizations
                ->map(fn (Organization $organization, int $index) => "[" . ($index + 1) . "] " . $organization->name)
                ->merge([null])
                ->merge(['[b] Back'])
                ->merge(['[q] Quit'])
                ->toArray());

            $value = $this->ask("Select target organization:", 1);

            if ($organization = is_numeric($value) ? $organizations[$value - 1] ?? null : null) {
                $this->askTargetOrganizationActions($organization);
            }

            if (strtolower($value) == 'b') {
                $this->showMainMenu();
            }

            $this->handleGlobalCommands($value);
            $this->printInvalidCommand($value);
        } while(true);
    }

    /**
     * @param Organization $organization
     * @return void
     */
    protected function askTargetOrganizationActions(Organization $organization): void
    {
        do {
            $this->printHeader("Select organization action:");
            $this->printList([
                '[1] View payments',
                '[2] Find payment by ID',
                '[3] Show bank account balance',
                null,
                '[b] Back',
                '[q] Quit',
            ]);

            $value = $this->ask("Select action", 1);

            if ($value == 1) {
                $this->showPayments($organization);
            }

            if ($value == 2) {
                $this->showPayment($organization);
            }

            if ($value == 3) {
                $this->showBalance($organization);
            }

            if (strtolower($value) == 'b') {
                $this->selectOrganizationCommand();
            }

            $this->handleGlobalCommands($value);
            $this->printInvalidCommand($value);
        } while (true);
    }

    /**
     * @param Organization $organization
     * @return void
     */
    protected function showPayments(Organization $organization): void
    {
        do {
            $perPage = $this->ask('How many payments would you like to fetch?', 100);

            if (!is_numeric($perPage)) {
                $this->printText("Invalid value [$perPage].");
            } else {
                $this->printText("Fetching last $perPage payments.");
            }
        }  while (!is_numeric($perPage));

        $payments = $organization->bank_connection_active->fetchPayments($perPage);

        if (is_null($payments)) {
            $this->printBankApiError("Could not fetch payments for \"$organization->name\".");
            $this->askTargetOrganizationActions($organization);
            return;
        }

        if (count($payments) == 0) {
            $this->info("No payments found on \"$organization->name\" account.");
            $this->askTargetOrganizationActions($organization);
            return;
        }

        $payments = array_map(function ($index) use ($payments) {
            return $this->bankPaymentToRow($payments[$index], $index + 1);
        }, array_keys($payments));

        $this->table(array_keys($payments[0]), $payments);
        $this->printSeparator();

        $this->askTargetOrganizationActions($organization);
    }

    /**
     * @param Organization $organization
     * @return void
     */
    protected function showPayment(Organization $organization): void
    {
        $paymentId = (string) $this->ask('Payment ID:');

        try {
            $payment = $organization->bank_connection_active->fetchPayment($paymentId);
            $payments = [$this->bankPaymentToRow($payment)];

            $this->table(array_keys($payments[0]), $payments);
            $this->printText(json_encode($payment->getRaw(), JSON_PRETTY_PRINT));
            $this->printSeparator();
        } catch (\Exception $exception) {
            $this->printBankApiError(
                $exception->getMessage(),
                "The payment_id could be invalid or the token could be invalid/expired.",
            );
        }

        $this->askTargetOrganizationActions($organization);
    }

    /**
     * @param Organization $organization
     * @return void
     */
    protected function showBalance(Organization $organization): void
    {
        $balance = $organization->bank_connection_active->fetchBalance();

        if (is_null($balance)) {
            $this->printBankApiError("Could not fetch balance for \"$organization->name\".");
            $this->askTargetOrganizationActions($organization);
            return;
        }

        $data = [
            'amount' => $balance->getAmount(),
            'currency' => $balance->getCurrency()
        ];

        $this->table(array_keys($data), [$data]);
        $this->printSeparator();

        $this->askTargetOrganizationActions($organization);
    }

    /**
     * @param BankPayment $payment
     * @param int|null $nth
     * @return array
     */
    protected function bankPaymentToRow(BankPayment $payment, ?int $nth = null): array
    {
        return array_merge($nth !== null ? [
            'nth' => $nth,
        ] : [], [
            'id' => $payment->getId(),
            'amount' => $payment->getAmount() . ' ' . $payment->getCurrency(),
            'date' => $payment->getDate() ?: 'No date',
            'description' => $payment->getDescription(),
        ]);
    }

    /**
     * @param string $message
     * @param string $reason
     * @return void
     */
    protected function printBankApiError(
        string $message,
        string $reason = "The token could be expired or invalid.",
    ): void {
        $this->error($message);
        $this->error($reason);
        $this->printText();
    }

    /**
     * @return Builder|Organization
     */
    protected function getOrganizationsQuery(): Builder|Organization
    {
        return Organization::whereHas('bank_connection_active');
    }

    /**
     * @param string $value
     * @return void
     */
    protected function handleGlobalCommands(string $value): void
    {
        if (strtolower($value) == 'q') {
            $this->exit();
        }
    }

    /**
     * @param string $value
     * @return void
     */
    protected function printInvalidCommand(string $value): void
    {
        $this->printText("Invalid input [$value]!\nPlease try again.\n");
    }
}
