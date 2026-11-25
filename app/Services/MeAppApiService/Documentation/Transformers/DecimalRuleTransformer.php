<?php

namespace App\Services\MeAppApiService\Documentation\Transformers;

use App\Services\MeAppApiService\Documentation\Schemas\DecimalType;
use Dedoc\Scramble\Contracts\RuleTransformer;
use Dedoc\Scramble\Support\Generator\Types\NumberType;
use Dedoc\Scramble\Support\Generator\Types\Type;
use Dedoc\Scramble\Support\RuleTransforming\NormalizedRule;
use Dedoc\Scramble\Support\RuleTransforming\RuleTransformerContext;

class DecimalRuleTransformer implements RuleTransformer
{
    /**
     * @param NormalizedRule $rule
     * @return bool
     */
    public function shouldHandle(NormalizedRule $rule): bool
    {
        return $rule->is('decimal') && $rule->parameters !== [];
    }

    /**
     * @param Type $previous
     * @param NormalizedRule $rule
     * @param RuleTransformerContext $context
     * @return DecimalType
     */
    public function toSchema(Type $previous, NormalizedRule $rule, RuleTransformerContext $context): DecimalType
    {
        $type = new DecimalType((int) ($rule->parameters[1] ?? $rule->parameters[0]));
        $type->addProperties($previous);

        if ($previous instanceof NumberType) {
            $type->setMin($previous->min)->setMax($previous->max);
        }

        return $type;
    }
}
