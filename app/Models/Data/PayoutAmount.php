<?php

namespace App\Models\Data;

use App\Helpers\Number;
use App\Models\FundAmountPreset;

class PayoutAmount
{
    /**
     * @param string $field
     * @param string $amount
     * @param FundAmountPreset|null $preset
     */
    public function __construct(
        protected string $field,
        protected string $amount,
        protected ?FundAmountPreset $preset,
    ) {
    }

    /**
     * @return string
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * @return string
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * @return FundAmountPreset|null
     */
    public function getPreset(): ?FundAmountPreset
    {
        return $this->preset;
    }

    /**
     * @param string $availableAmount
     * @return bool
     */
    public function exceeds(string $availableAmount): bool
    {
        return Number::toCents((float) $this->amount) > Number::toCents((float) $availableAmount);
    }
}
