<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class DemoTransaction
 * @property mixed $id
 * @property string $token
 * @property string $state
 * @package App\Models
 */
class DemoTransaction extends Model
{
    const STATE_PENDING  = 'pending';
    const STATE_ACCEPTED = 'accepted';
    const STATE_REJECTED = 'rejected';
    const STATES = [self::STATE_PENDING, self::STATE_ACCEPTED, self::STATE_REJECTED];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'token', 'state'
    ];
}