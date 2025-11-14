<?php

namespace App\Events\FundProviders;

use App\Models\FundProvider;
use Illuminate\Support\Arr;

class FundProviderStateUpdated extends BaseFundProviderEvent
{
    protected mixed $approvedBefore;
    protected mixed $approvedAfter;
    protected mixed $originalState;
    protected ?string $note;

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
        $this->note = Arr::get($eventData, 'note');
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

    /**
     * @return string|null
     */
    public function getNote(): ?string
    {
        return $this->note;
    }
}
