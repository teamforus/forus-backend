<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Implementation;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('implementations', function (Blueprint $table) {
            $table->string('currency_sign', 10)->default(Implementation::CURRENCY_SIGN_EUR)->after('allow_per_fund_notification_templates');
            $table->boolean('currency_round')->default(false)->after('currency_sign');
        });

        Implementation::query()->whereIn('key', Implementation::WITH_CURRENCY_COIN)->update([
            'currency_sign' => Implementation::CURRENCY_SIGN_COIN,
        ]);

        Implementation::query()->whereIn('key', Implementation::WITH_CURRENCY_ROUND)->update([
            'currency_round' => true,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('implementations', function (Blueprint $table) {
            $table->dropColumn('currency_sign');
            $table->dropColumn('currency_round');
        });
    }
};
