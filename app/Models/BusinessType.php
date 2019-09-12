<?php

namespace App\Models;

use App\Models\Traits\EloquentModel;
use Carbon\Carbon;
use Dimsav\Translatable\Translatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Class BusinessType
 * @property mixed $id
 * @property string $key
 * @property string $name
 * @property integer $parent_id
 * @property BusinessType $parent
 * @property Collection $organizations
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @package App\Models
 */
class BusinessType extends Model
{
    use EloquentModel, Translatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'key', 'parent_id'
    ];

    /**
     * The relations to eager load on every query.
     *
     * @var array
     */
    protected $with = [];

    /**
     * The attributes that are translatable.
     *
     * @var array
     */
    public $translatedAttributes = [
        'name'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function organizations() {
       return $this->hasMany(Organization::class);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function search(Request $request) {
        $query = self::query();

        if ($request->input('used', false)) {
            $query->has('organizations.supplied_funds_approved');
        }

        return $query;
    }
}
