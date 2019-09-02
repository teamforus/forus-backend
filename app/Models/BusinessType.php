<?php

namespace App\Models;

use App\Models\Traits\EloquentModel;
use Dimsav\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class BusinessType extends Model
{
    use EloquentModel, Translatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'key'
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
