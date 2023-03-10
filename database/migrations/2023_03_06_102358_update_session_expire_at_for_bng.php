<?php

use App\Models\BankConnection;
use App\Services\BankService\Models\Bank;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        BankConnection::whereHas('bank', fn(Builder $q) => $q->where('key', Bank::BANK_BNG))
            ->get()
            ->each(function (BankConnection $connection) {
                $expireAt = $connection->created_at->add(
                    config('forus.bng.expire_time.unit'),
                    config('forus.bng.expire_time.value')
                );
                $connection->update(['session_expire_at' => $expireAt]);
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void {}
};
