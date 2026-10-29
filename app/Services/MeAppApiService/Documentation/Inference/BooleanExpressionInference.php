<?php

namespace App\Services\MeAppApiService\Documentation\Inference;

use Dedoc\Scramble\Infer\Extensions\ExpressionTypeInferExtension;
use Dedoc\Scramble\Infer\Scope\Scope;
use Dedoc\Scramble\Support\Type\BooleanType;
use Dedoc\Scramble\Support\Type\Type;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\BooleanOr;
use PhpParser\Node\Expr\BinaryOp\LogicalAnd;
use PhpParser\Node\Expr\BinaryOp\LogicalOr;
use PhpParser\Node\Expr\BinaryOp\LogicalXor;

class BooleanExpressionInference implements ExpressionTypeInferExtension
{
    /**
     * @param Expr $node
     * @param Scope $scope
     * @return Type|null
     */
    public function getType(Expr $node, Scope $scope): ?Type
    {
        return $node instanceof BooleanAnd || $node instanceof BooleanOr ||
            $node instanceof LogicalAnd || $node instanceof LogicalOr || $node instanceof LogicalXor
                ? new BooleanType()
                : null;
    }
}
