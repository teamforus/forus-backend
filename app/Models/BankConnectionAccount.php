<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\BankConnectionAccount
 *
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnectionAccount newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnectionAccount newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BankConnectionAccount query()
 * @mixin \Eloquent
 */
class BankConnectionAccount extends Model
{
    protected $fillable = [
        'bank_connection_id', 'monetary_account_id', 'monetary_account_iban',
        'monetary_account_name', 'type',
    ];
}
