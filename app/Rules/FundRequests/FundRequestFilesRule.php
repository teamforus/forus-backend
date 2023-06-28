<?php

namespace App\Rules\FundRequests;

class FundRequestFilesRule extends BaseFundRequestRule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, mixed $value): bool
    {
        $files = $value['files'] ?? [];

        $criteria = $this->fund->criteria()->where([
            'id' => $value['fund_criterion_id'] ?? false,
        ])->first();

        if (!$criteria) {
            $this->msg = trans('validation.in', [
                'attribute' => trans('validation.attributes.file'),
            ]);
            return false;
        }

        if ($criteria->show_attachment && (!is_array($files) || count($files) < 1)) {
            $this->msg = trans('validation.required', [
                'attribute' => trans('validation.attributes.file'),
            ]);
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
        return $this->msg;
    }
}
