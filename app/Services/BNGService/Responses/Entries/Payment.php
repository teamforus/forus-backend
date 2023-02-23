<?php

namespace App\Services\BNGService\Responses\Entries;

use App\Models\BankHoliday;
use Carbon\Carbon;

class Payment
{
    protected $amount;
    protected $debtor;
    protected $creditor;
    protected $paymentId;
    protected $description;
    protected $requestedExecutionDate;

    /**
     * @param Amount $amount
     * @param Account $debtor
     * @param Account $creditor
     * @param string|null $paymentId
     * @param string $description
     * @param string|null $requestedExecutionDate
     */
    public function __construct(
        Amount $amount,
        Account $debtor,
        Account $creditor,
        string $paymentId = null,
        string $description = '',
        string $requestedExecutionDate = null
    ) {
        $this->amount = $amount;
        $this->debtor = $debtor;
        $this->creditor = $creditor;
        $this->paymentId = $paymentId;
        $this->description = $description;
        $this->requestedExecutionDate = $requestedExecutionDate;
    }

    /**
     * @return Amount
     */
    public function getAmount(): Amount
    {
        return $this->amount;
    }

    /**
     * @return Account
     */
    public function getDebtor(): Account
    {
        return $this->debtor;
    }

    /**
     * @return Account
     */
    public function getCreditor(): Account
    {
        return $this->creditor;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return string|null
     */
    public function getRequestedExecutionDate(): ?string
    {
        return $this->requestedExecutionDate ?: $this::getNextBusinessDay();
    }

    /**
     * @return string|null
     */
    public function getPaymentId(): ?string
    {
        return $this->paymentId;
    }

    /**
     * @return Carbon
     */
    public static function getNextBusinessDay(): Carbon
    {
        $date = now()->addDay();

        while (!self::isWorkingDay($date)) {
            $date->addDay();
        }

        return $date;
    }

    /**
     * @param Carbon $date
     * @return bool
     */
    public static function isWorkingDay(Carbon $date): bool
    {
        return !$date->isWeekend() &&
            !BankHoliday::query()->where('date', $date->toDateString())->exists();
    }
}