<?php

namespace App\Modules\RequesterImport\Models;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $bsn
 */
class RequesterImportRow extends Model
{
    use SoftDeletes;

    protected $fillable = ['organization_id', 'bsn'];

    /**
     * @return BelongsTo
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return HasMany
     */
    public function records(): HasMany
    {
        return $this->hasMany(RequesterImportRecord::class)->latest('id');
    }

    /**
     * @param string $value
     * @return void
     */
    public function setBsnAttribute(string $value): void
    {
        $this->attributes['bsn'] = str_pad(trim($value), 9, '0', STR_PAD_LEFT);
    }
}
