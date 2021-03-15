<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\FundProviderChatMessage
 *
 * @property int $id
 * @property int $fund_provider_chat_id
 * @property string $message
 * @property string|null $identity_address
 * @property string $counterpart
 * @property string|null $seen_at
 * @property bool $provider_seen
 * @property bool $sponsor_seen
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\FundProviderChat $fund_provider_chat
 * @method static \Illuminate\Database\Eloquent\Builder|FundProviderChatMessage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundProviderChatMessage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundProviderChatMessage query()
 * @method static \Illuminate\Database\Eloquent\Builder|FundProviderChatMessage whereCounterpart($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundProviderChatMessage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundProviderChatMessage whereFundProviderChatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundProviderChatMessage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundProviderChatMessage whereIdentityAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundProviderChatMessage whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundProviderChatMessage whereProviderSeen($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundProviderChatMessage whereSeenAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundProviderChatMessage whereSponsorSeen($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundProviderChatMessage whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FundProviderChatMessage extends Model
{
    /**
     * @var int
     */
    protected $perPage = 20;

    /**
     * @var array
     */
    protected $fillable = [
        'fund_provider_chat_id', 'message', 'identity_address',
        'counterpart', 'seen_at', 'sponsor_seen', 'provider_seen'
    ];

    /**
     * @var array
     */
    public $timestamps = [
        'seen_at'
    ];

    /**
     * @var array
     */
    protected $casts = [
        'sponsor_seen' => 'bool',
        'provider_seen' => 'bool',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fund_provider_chat() {
        return $this->belongsTo(FundProviderChat::class);
    }
}
