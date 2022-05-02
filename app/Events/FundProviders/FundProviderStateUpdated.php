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
     * @param FundProvider $fundProvider
     * @param array $eventData
     */
    public function __construct(FundProvider $fundProvider, array $eventData)
    {
        parent::__construct($fundProvider);

        $this->approvedBefore = Arr::get($eventData, 'approvedBefore');
        $this->approvedAfter = Arr::get($eventData, 'approvedAfter');
        $this->originalState = Arr::get($eventData, 'originalState');
    }

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
