<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\BankConnectionAccount
 *
 * @property int $id
 * @property int $bank_connection_id
 * @property string|null $monetary_account_id
 * @property string|null $monetary_account_iban
 * @property string|null $monetary_account_name
 * @property string $type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnectionAccount newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnectionAccount newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnectionAccount query()
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnectionAccount whereBankConnectionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnectionAccount whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnectionAccount whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnectionAccount whereMonetaryAccountIban($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnectionAccount whereMonetaryAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnectionAccount whereMonetaryAccountName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnectionAccount whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnectionAccount whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class BankConnectionAccount extends Model
{
    protected $fillable = [
        'bank_connection_id', 'monetary_account_id', 'monetary_account_iban',
        'monetary_account_name', 'type',
    ];
}
