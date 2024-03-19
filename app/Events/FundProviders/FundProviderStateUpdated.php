<?php

namespace App\Events\FundProviders;

use App\Models\FundProvider;
use Illuminate\Support\Arr;

class FundProviderStateUpdated extends BaseFundProviderEvent
{
    protected $approvedBefore;
    protected $approvedAfter;
    protected $originalState;

    /**
     * @return string
     */
    public function getOriginalState(): string
    {
        return $this->originalState;
    }

    /**
     * @return bool
     */
    public function getApprovedBefore(): bool
    {
        return $this->approvedBefore;
    }

    /**
     * @return bool
     */
    public function getApprovedAfter(): bool
    {
        return $this->approvedAfter;
    }
}
