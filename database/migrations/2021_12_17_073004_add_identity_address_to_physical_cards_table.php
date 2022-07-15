<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\PhysicalCard;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('physical_cards', function (Blueprint $table) {
            $table->string('identity_address', 200)->nullable()->after('code');
        });

        foreach (PhysicalCard::whereHas('voucher')->get() as $card) {
            $card->forceFill($card->voucher->only('identity_address'))->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('physical_cards', function (Blueprint $table) {
            $table->dropColumn('identity_address');
        });
    }
};
