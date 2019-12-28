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
 * @property-read string|null $created_at_locale
 * @property-read string|null $updated_at_locale
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DemoTransaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DemoTransaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DemoTransaction query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DemoTransaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DemoTransaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DemoTransaction whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DemoTransaction whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DemoTransaction whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class DemoTransaction extends Model
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
        'token', 'state'
    ];
}