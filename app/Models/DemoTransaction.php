<?php

namespace App\Models;

/**
 * App\Models\DemoTransaction
 *
 * @property int $id
 * @property string $token
 * @property string $state
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|DemoTransaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DemoTransaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DemoTransaction query()
 * @method static \Illuminate\Database\Eloquent\Builder|DemoTransaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DemoTransaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DemoTransaction whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DemoTransaction whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DemoTransaction whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class DemoTransaction extends BaseModel
{
    const STATE_PENDING  = 'pending';
    const STATE_ACCEPTED = 'accepted';
    const STATE_REJECTED = 'rejected';

    const STATES = [
        self::STATE_PENDING,
        self::STATE_ACCEPTED,
        self::STATE_REJECTED
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