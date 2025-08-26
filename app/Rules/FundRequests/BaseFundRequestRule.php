<?php

namespace App\Rules\FundRequests;

use App\Helpers\Arr;
use App\Helpers\Validation;
use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\FundCriterion;
use App\Models\RecordType;
use App\Rules\BaseRule;
use App\Rules\FundRequests\RecordTypes\BaseRecordTypeRule;
use App\Rules\FundRequests\RecordTypes\RecordTypeBoolRule;
use App\Rules\FundRequests\RecordTypes\RecordTypeDateRule;
use App\Rules\FundRequests\RecordTypes\RecordTypeEmailRule;
use App\Rules\FundRequests\RecordTypes\RecordTypeIbanRule;
use App\Rules\FundRequests\RecordTypes\RecordTypeNumericRule;
use App\Rules\FundRequests\RecordTypes\RecordTypeSelectNumberRule;
use App\Rules\FundRequests\RecordTypes\RecordTypeSelectRule;
use App\Rules\FundRequests\RecordTypes\RecordTypeStringRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use LogicException;

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
    ) {
    }

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
            $recordType::TYPE_SELECT_NUMBER => Validation::check($value, new RecordTypeSelectNumberRule($criterion)),
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
        if (!str_starts_with($attribute, 'records.')) {
            throw new LogicException("Invalid attribute path in BaseFundRequestRule::findCriterion: '$attribute'");
        }

        $index = explode('.', $attribute)[1] ?? null;
        $record = $this->request->input("records.$index");

        return $this->fund->criteria()->find($record['fund_criterion_id'] ?? null);
    }

    /**
     * @param array $records
     * @return array
     */
    protected function mapRecordValues(array $records): array
    {
        $fundCriteriaById = $this->fund->criteria->pluck('record_type_key', 'id');

        return array_reduce(array_keys($records), function ($list, $fund_criterion_id) use (
            $fundCriteriaById,
            $records,
        ) {
            $value = $records[$fund_criterion_id] ?? null;

            if ($fundCriteriaById[$fund_criterion_id] ?? false) {
                return [...$list, $fundCriteriaById[$fund_criterion_id] => $value];
            }

            return $list;
        }, []);
    }
    /**
     * @param FundCriterion $criterion
     * @param ?string $label
     * @return BaseRecordTypeRule
     */
    public static function recordTypeRuleFor(FundCriterion $criterion, string $label = null): BaseRecordTypeRule
    {
        $recordType = RecordType::where('key', Arr::get($criterion, 'record_type_key'))->first();

        return match ($recordType->type) {
            $recordType::TYPE_STRING => new RecordTypeStringRule($criterion, $label),
            $recordType::TYPE_NUMBER => new RecordTypeNumericRule($criterion, $label),
            $recordType::TYPE_SELECT => new RecordTypeSelectRule($criterion, $label),
            $recordType::TYPE_SELECT_NUMBER => new RecordTypeSelectNumberRule($criterion, $label),
            $recordType::TYPE_EMAIL => new RecordTypeEmailRule($criterion, $label),
            $recordType::TYPE_IBAN => new RecordTypeIbanRule($criterion, $label),
            $recordType::TYPE_BOOL => new RecordTypeBoolRule($criterion, $label),
            $recordType::TYPE_DATE => new RecordTypeDateRule($criterion, $label),
            default => null,
        };
    }
}
