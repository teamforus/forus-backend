<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\DemoTransaction.
 *
 * @property int $id
 * @property string $token
 * @property string $state
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DemoTransaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DemoTransaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DemoTransaction query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DemoTransaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DemoTransaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DemoTransaction whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DemoTransaction whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DemoTransaction whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class DemoTransaction extends Model
{
    public const string STATE_PENDING = 'pending';
    public const string STATE_ACCEPTED = 'accepted';
    public const string STATE_REJECTED = 'rejected';

    public const array STATES = [
        self::STATE_PENDING,
        self::STATE_ACCEPTED,
        self::STATE_REJECTED,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'token', 'state',
    ];
}
