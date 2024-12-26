<?php

namespace App\Rules\FundRequests\FundRequestRecords;

use App\Helpers\Validation;
use App\Models\FundCriterion;
use App\Rules\FundRequests\BaseFundRequestRule;
use App\Services\FileService\Models\File;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

class FundRequestRecordFilesRule extends BaseFundRequestRule
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
        $criterion = $this->findCriterion($attribute);

        if (!$criterion) {
            return $this->reject(trans('validation.in', compact('attribute')));
        }

        // when attachments are not requested the files must not be provided
        if (!$criterion->show_attachment) {
            return empty($value) || $this->reject(trans('validation.in', compact('attribute')));
        }

        // files must be an array (if criterion not optional - not empty array)
        if (($validation = Validation::check($value, $this->filesRules($criterion)))->fails()) {
            return $this->reject($validation->errors()->first('value'));
        }

        // validate each file
        foreach ($value as $index => $file) {
            $validation = Validation::check($file, [
                $this->isRequiredRule($criterion),
                Rule::exists('files', 'uid')->where(function(Builder|File $builder) {
                    $builder->where('identity_address', $this->request->auth_address());
                    $builder->where('type', 'fund_request_record_proof');
                    $builder->whereNull('fileable_type');
                    $builder->whereNull('fileable_id');
                }),
            ], $attribute . '.' . $index);

            if ($validation->fails()) {
                return $this->reject($validation->errors()->first('value'));
            }
        }

        return true;
    }

    /**
     * @param FundCriterion $criterion
     * @return string
     */
    private function filesRules(FundCriterion $criterion): string
    {
        if ($criterion->optional) {
            return 'nullable|array';
        }

        return 'required|array|min:1';
    }

    /**
     * @param FundCriterion $criterion
     * @return string
     */
    private function isRequiredRule(FundCriterion $criterion): string
    {
        return $criterion->optional ? 'nullable' : 'required';
    }
}
