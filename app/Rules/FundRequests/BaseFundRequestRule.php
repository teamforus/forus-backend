<?php

namespace App\Rules\FundRequests;

use App\Helpers\Arr;
use App\Helpers\Validation;
use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\FundCriterion;
use App\Models\RecordType;
use App\Rules\BaseRule;
use App\Rules\FundRequests\RecordTypes\RecordTypeBoolRule;
use App\Rules\FundRequests\RecordTypes\RecordTypeDateRule;
use App\Rules\FundRequests\RecordTypes\RecordTypeEmailRule;
use App\Rules\FundRequests\RecordTypes\RecordTypeIbanRule;
use App\Rules\FundRequests\RecordTypes\RecordTypeNumericRule;
use App\Rules\FundRequests\RecordTypes\RecordTypeSelectRule;
use App\Rules\FundRequests\RecordTypes\RecordTypeStringRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

abstract class BaseFundRequestRule extends BaseRule
{

    /**
     * Create a new rule instance.
     *
     * @param Fund|null $fund
     * @param BaseFormRequest|null $request
     */
    public function __construct(
        protected ?Fund $fund,
        protected ?BaseFormRequest $request = null,
    ) {}

    /**
     * @param FundCriterion $criterion
     * @param mixed $value
     * @return Validator
     */
    public static function validateRecordValue(FundCriterion $criterion, mixed $value): Validator
    {
        $recordType = RecordType::where('key', Arr::get($criterion, 'record_type_key'))->first();

        return match ($recordType->type) {
            $recordType::TYPE_STRING => Validation::check($value, new RecordTypeStringRule($criterion)),
            $recordType::TYPE_NUMBER => Validation::check($value, new RecordTypeNumericRule($criterion)),
            $recordType::TYPE_SELECT => Validation::check($value, new RecordTypeSelectRule($criterion)),
            $recordType::TYPE_EMAIL => Validation::check($value, new RecordTypeEmailRule($criterion)),
            $recordType::TYPE_IBAN => Validation::check($value, new RecordTypeIbanRule($criterion)),
            $recordType::TYPE_BOOL => Validation::check($value, new RecordTypeBoolRule($criterion)),
            $recordType::TYPE_DATE => Validation::check($value, new RecordTypeDateRule($criterion)),
            default => Validation::check('', ['required', Rule::in([])]),
        };
    }

    /**
     * @param string $attribute
     * @return FundCriterion|null
     */
    protected function findCriterion(string $attribute): ?FundCriterion
    {
        $row = $this->getRecordRow($attribute);

        return $this->fund->criteria()->find($row['fund_criterion_id'] ?? null);
    }

    /**
     * @param string $attribute
     * @return array
     */
    protected function getRecordRow(string $attribute): array
    {
        return $this->request->input(implode('.', array_slice(explode('.', $attribute), 0, -1)));
    }
}