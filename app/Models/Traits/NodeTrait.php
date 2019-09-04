<?php

namespace App\Models\Traits;

use App\Models\Model;
use Kalnoy\Nestedset\Collection;

/**
 * Trait NodeTrait
 * @method static int fixTree(NodeTrait|Model $root = null)
 * @method static Collection descendantsAndSelf($id, array $columns = [ '*' ])
 * @package App\Models\Traits
 */
trait NodeTrait
{
    use \Kalnoy\Nestedset\NodeTrait;
}