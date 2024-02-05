<?php

namespace App\Rules\FundCriteria;

use App\Helpers\Validation;
use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Models\RecordType;
use App\Rules\BaseRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Validator;

abstract class BaseFundCriteriaRule extends BaseRule
{
    /**
     * @var string
     */
    protected string $dateFormat = 'd-m-Y';

    /**
     * @param BaseFormRequest $request
     * @param Organization $organization
     */
    public function __construct(
        protected BaseFormRequest $request,
        protected Organization $organization,
    ) {}

    /**
     * @param string $key
     * @return RecordType|null
     */
    protected function findRecordType(string $key): ?RecordType
    {
        return RecordType::where([
            'key' => $key,
            'criteria' => true,
        ])->where(function (Builder|RecordType $builder) {
            $builder->where('organization_id', $this->organization->id);
            $builder->orWhereNull('organization_id');
        })->first();
    }

    /**
     * @param string $attribute
     * @return array
     */
    protected function getCriteriaRow(string $attribute): array
    {
        return $this->request->input(implode('.', array_slice(explode('.', $attribute), 0, -1)));
    }

    /**
     * @param mixed $value
     * @return bool
     */
    protected function isValidDate(mixed $value): bool
    {
        return Validation::check($value, "required|date|date_format:$this->dateFormat")->passes();
    }

    /**
     * @param string $attribute
     * @param mixed $min
     * @param mixed $max
     * @return Validator
     */
    protected function validateMinMax(string $attribute, mixed $min, mixed $max): Validator
    {
        $recordType = $this->findRecordType(Arr::get($this->getCriteriaRow($attribute), 'record_type_key'));

        if (!$recordType) {
            return ValidatorFacade::make([
                'min' => 'invalid',
                'max' => 'invalid',
            ], [
                'min' => 'required|in',
                'max' => 'required|in',
            ]);
        }

        return match($recordType->type) {
            $recordType::TYPE_DATE  => ValidatorFacade::make(compact('min', 'max'), [
                'min' => implode('', [
                    "nullable|date|date_format:$this->dateFormat",
                    $this->isValidDate($max) ? "|before_or_equal:$max" : "",
                ]),
                'max' => implode('', [
                    "nullable|date|date_format:$this->dateFormat",
                    $this->isValidDate($min) ? "|after_or_equal:$min" : "",
                ]),
            ]),
            default => ValidatorFacade::make(compact('min', 'max'), [
                'min' => 'nullable|numeric' . (is_numeric($max) ? '|lte:max' : ''),
                'max' => 'nullable|numeric' . (is_numeric($min) ? '|gte:min' : ''),
            ]),
        };
    }
}
