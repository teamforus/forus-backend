<?php

namespace App\Models\Traits;

use App\Models\BaseModel;
use Kalnoy\Nestedset\Collection;
use Kalnoy\Nestedset\DescendantsRelation;

/**
 * Trait NodeTrait
 * @property NodeTrait[]|\Illuminate\Database\Eloquent\Collection $descendants
 * @property NodeTrait[]|\Illuminate\Database\Eloquent\Collection $descendants_min
 * @method static int fixTree(NodeTrait|BaseModel $root = null)
 * @method static Collection descendantsAndSelf($id, array $columns = [ '*' ])
 * @package App\Models\Traits
 * @mixin \Eloquent
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