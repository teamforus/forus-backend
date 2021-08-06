<?php

namespace App\Models\Traits;

use App\Models\Model;
use Kalnoy\Nestedset\Collection;
use Kalnoy\Nestedset\DescendantsRelation;

/**
 * Trait NodeTrait
 * @property NodeTrait[]|\Illuminate\Database\Eloquent\Collection $descendants
 * @property NodeTrait[]|\Illuminate\Database\Eloquent\Collection $descendants_min
 * @method static int fixTree(NodeTrait|Model $root = null)
 * @method static Collection descendantsAndSelf($id, array $columns = [ '*' ])
 * @package App\Models\Traits
 */
trait NodeTrait
{
    use \Kalnoy\Nestedset\NodeTrait;

    /**
     * Get query for descendants of the node.
     *
     * @return DescendantsRelation
     * @noinspection PhpUnused
     */
    public function descendants_min(): DescendantsRelation
    {
        return $this->descendants()->select('id', $this->getRgtName(), $this->getLftName());
    }
}