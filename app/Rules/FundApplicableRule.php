<?php

namespace App\Rules;

use App\Models\FundProvider;
use App\Models\Organization;
use Illuminate\Contracts\Validation\Rule;
use Predis\Command\Redis\STRLEN;

class FundApplicableRule implements Rule
{
    private Organization $organization;
    private string $message = 'The validation error message.';

    /**
     * Create a new rule instance.
     *
     * FundApplicableRule constructor.
     * @param Organization $organization
     */
    public function __construct(
        Organization $organization
    ) {
        $this->organization = $organization;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        if ($this->organization->fund_providers()->where('fund_id', $value)->exists()) {
            $this->message = trans('validation.organization_fund.already_requested');

            return false;
        }

        if (!FundProvider::queryAvailableFunds($this->organization)->where('id', $value)->exists()) {
            $this->message = trans('validation.organization_fund.not_allowed');

            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return $this->message;
    }
}
