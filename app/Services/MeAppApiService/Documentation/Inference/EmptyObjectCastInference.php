<?php

namespace App\Services\MeAppApiService\Documentation\Inference;

use Dedoc\Scramble\Infer\Extensions\ExpressionTypeInferExtension;
use Dedoc\Scramble\Infer\Scope\Scope;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\Cast\Object_;
use stdClass;

class EmptyObjectCastInference implements ExpressionTypeInferExtension
{
    /**
     * @param Expr $node
     * @param Scope $scope
     * @return Type|null
     */
    public function getType(Expr $node, Scope $scope): ?Type
    {
        if (!$node instanceof Object_ || !$node->expr instanceof Array_ || $node->expr->items !== []) {
            return null;
        }

        return new ObjectType(stdClass::class);
    }
}
