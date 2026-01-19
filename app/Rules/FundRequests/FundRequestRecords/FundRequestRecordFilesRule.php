<?php

namespace App\Rules\FundRequests\FundRequestRecords;

use App\Helpers\Validation;
use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\FundCriterion;
use App\Models\RecordType;
use App\Rules\FundRequests\BaseFundRequestRule;
use App\Services\FileService\Models\File;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

class FundRequestRecordFilesRule extends BaseFundRequestRule
{
    /**
     * Create a new rule instance.
     *
     * @param Fund|null $fund
     * @param BaseFormRequest|null $request
     * @param array $submittedRecords
     * @param array $submittedRawRecords
     */
    public function __construct(
        protected ?Fund $fund,
        protected ?BaseFormRequest $request,
        protected array $submittedRecords,
        protected array $submittedRawRecords,
    ) {
        parent::__construct($fund, $request);
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, mixed $value): bool
    {
        $criterion = $this->findCriterion($attribute, $this->submittedRawRecords);
        $label = trans('validation.attributes.file');

        if (!$criterion) {
            return $this->reject(__('validation.in', [$attribute => $label]));
        }

        // when attachments are not requested the files must not be submitted
        if (!$criterion->show_attachment) {
            if (!empty($value)) {
                return $this->reject(__('validation.in', [$attribute => $label]));
            }

            return true;
        }

        // value is an array
        if (!is_array($value)) {
            return $this->reject(__('validation.array', [$attribute => $label]));
        }

        $validator = Validation::check($value, $this->filesRules($criterion), $label);

        // files must be an array (if criterion not optional - not empty array)
        if ($validator->fails()) {
            return $this->reject($validator->errors()->first('value'));
        }

        // validate each file
        foreach ($value as $index => $file) {
            $validation = Validation::check($file, [
                $this->isRequiredRule($criterion) ? 'required' : 'nullable',
                Rule::exists('files', 'uid')->where(function (Builder|File $builder) {
                    $builder->where('identity_address', $this->request->auth_address());
                    $builder->where('type', 'fund_request_record_proof');
                    $builder->whereNull('fileable_type');
                    $builder->whereNull('fileable_id');
                }),
            ], $attribute . '.' . $index);

            if ($validation->fails()) {
                return $this->reject($validation->errors()->first($attribute . '.' . $index));
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
        if (!$this->isRequiredRule($criterion)) {
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
        $value = $this->mapRecordValues($this->submittedRecords)[$criterion->record_type_key] ?? null;

        if (
            $criterion->record_type->type === RecordType::TYPE_BOOL &&
            $criterion->record_type->control_type === RecordType::CONTROL_TYPE_CHECKBOX &&
            $value === RecordType::TYPE_BOOL_OPTION_YES
        ) {
            return true;
        }

        return !$criterion->optional;
    }
}
