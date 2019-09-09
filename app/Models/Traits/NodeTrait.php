<?php

namespace App\Models\Traits;

use App\Models\Model;
use Kalnoy\Nestedset\Collection;

/**
 * Trait NodeTrait
 * @property NodeTrait[]|\Illuminate\Database\Eloquent\Collection $descendants
 * @method static int fixTree(NodeTrait|Model $root = null)
 * @method static Collection descendantsAndSelf($id, array $columns = [ '*' ])
 * @package App\Models\Traits
 */
trait NodeTrait
{
    use \Kalnoy\Nestedset\NodeTrait;
}