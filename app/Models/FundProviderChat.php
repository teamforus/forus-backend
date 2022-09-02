<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\FundProviderChat
 *
 * @property int $id
 * @property int|null $product_id
 * @property int|null $fund_provider_id
 * @property string $identity_address
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\FundProvider|null $fund_provider
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundProviderChatMessage[] $messages
 * @property-read int|null $messages_count
 * @property-read \App\Models\Product|null $product
 * @method static \Illuminate\Database\Eloquent\Builder|FundProviderChat newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundProviderChat newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundProviderChat query()
 * @method static \Illuminate\Database\Eloquent\Builder|FundProviderChat whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundProviderChat whereFundProviderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundProviderChat whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundProviderChat whereIdentityAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundProviderChat whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundProviderChat whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FundProviderChat extends Model
{
    const TYPE_SYSTEM = 'system';
    const TYPE_SPONSOR = 'sponsor';
    const TYPE_PROVIDER = 'provider';

    const TYPE = [
        self::TYPE_SYSTEM,
        self::TYPE_SPONSOR,
        self::TYPE_PROVIDER,
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'product_id', 'fund_provider_id', 'identity_address',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product() {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fund_provider() {
        return $this->belongsTo(FundProvider::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function messages(): HasMany {
        return $this->hasMany(FundProviderChatMessage::class);
    }

    /**
     * @param string $counterpart
     * @param string $message
     * @param string|null $identity_address
     * @return FundProviderChatMessage|BaseModel
     */
    public function addMessage(
        string $counterpart,
        string $message,
        ?string $identity_address = null
    ): FundProviderChatMessage|Model {
        return $this->messages()->create(array_merge(compact(
            'identity_address', 'message', 'counterpart'
        ), [
            'sponsor_seen' => $counterpart == 'sponsor',
            'provider_seen' => $counterpart == 'provider',
        ]));
    }

    /**
     * @param string $message
     * @param string $identity_address
     * @return FundProviderChatMessage
     */
    public function addSponsorMessage(string $message, string $identity_address) {
        return $this->addMessage(self::TYPE_SPONSOR, $message, $identity_address);
    }

    /**
     * @param string $message
     * @param string $identity_address
     * @return FundProviderChatMessage
     */
    public function addProviderMessage(string $message, string $identity_address) {
        return $this->addMessage(self::TYPE_PROVIDER, $message, $identity_address);
    }

    /**
     * @param string $message
     * @param string|null $identity_address
     * @return FundProviderChatMessage
     */
    public function addSystemMessage(string $message, ?string $identity_address = null) {
        return $this->addMessage(self::TYPE_SYSTEM, $message, $identity_address);
    }
}
