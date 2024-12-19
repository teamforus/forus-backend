<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fund_requests', function (Blueprint $table) {
            $table->unsignedInteger('identity_id')->nullable()->after('id');
            $table->foreign('identity_id')->references('id')->on('identities')->onDelete('restrict');
        });

        $this->updateVoucherIdentityIds();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->updateVoucherIdentityAddresses();

        Schema::table('fund_requests', function (Blueprint $table) {
            $table->dropForeign(['identity_id']);
            $table->dropColumn('identity_id');
        });
    }

    /**
     * @return void
     */
    private function updateVoucherIdentityIds(): void
    {
        $query = DB::table('fund_requests')
            ->whereNotNull('identity_address')
            ->whereNull('identity_id');

        while ((clone $query)->exists()) {
            (clone $query)->get()->each(function ($voucher) {
                $identity = DB::table('identities')
                    ->where('address', $voucher->identity_address)
                    ->first();

                if ($identity) {
                    DB::table('fund_requests')
                        ->where('id', $voucher->id)
                        ->update(['identity_id' => $identity->id]);
                }
            });
        }
    }

    /**
     * @return void
     */
    private function updateVoucherIdentityAddresses(): void
    {
        DB::table('fund_requests')->get()->each(function ($voucher) {
            $query = DB::table('fund_requests')
                ->whereNotNull('identity_id')
                ->whereNull('identity_address');

            while ((clone $query)->exists()) {
                (clone $query)->get()->each(function ($voucher) {
                    $identity = DB::table('identities')
                        ->where('id', $voucher->identity_id)
                        ->first();

                    if ($identity) {
                        DB::table('fund_requests')
                            ->where('id', $voucher->id)
                            ->update(['identity_address' => $identity->address]);
                    }
                });
            }
        });
    }
};
