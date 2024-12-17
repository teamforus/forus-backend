<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\BankConnectionAccount
 *
 * @property int $id
 * @property int $bank_connection_id
 * @property string $monetary_account_id
 * @property string $monetary_account_iban
 * @property string|null $monetary_account_name
 * @property string $type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankConnectionAccount newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankConnectionAccount newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankConnectionAccount query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankConnectionAccount whereBankConnectionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankConnectionAccount whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankConnectionAccount whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankConnectionAccount whereMonetaryAccountIban($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankConnectionAccount whereMonetaryAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankConnectionAccount whereMonetaryAccountName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankConnectionAccount whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankConnectionAccount whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class BankConnectionAccount extends Model
{
    protected $fillable = [
        'bank_connection_id', 'monetary_account_id', 'monetary_account_iban',
        'monetary_account_name', 'type',
    ];
}
