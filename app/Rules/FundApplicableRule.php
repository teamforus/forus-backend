<?php

namespace App\Rules;

use App\Models\FundProductCategory;
use App\Models\Organization;
use Illuminate\Contracts\Validation\Rule;

class FundApplicableRule implements Rule
{

    private $organization;
    private $message = "The validation error message.";

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
    public function passes($attribute, $value)
    {
        $fundIds = FundProductCategory::getModel()->whereIn(
            'product_category_id',
            $this->organization->product_categories->pluck('id')->toArray()
        )->select('fund_id')->distinct()->get()->pluck(
            'fund_id'
        );

        if ($fundIds->search($value) === false) {
            $this->message = trans(
                'validation.organization_fund.wrong_categories'
            );

            return false;
        }

        if (!empty($this->organization->organization_funds()->where(
            'fund_id', $value
        )->first())) {
            $this->message = trans(
                'validation.organization_fund.already_requested'
            );

            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->message;
    }
}
