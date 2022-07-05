<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Fund;

/**
 * @noinspection PhpUnused
 */
class AddDescriptionTextToFundsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('funds', function (Blueprint $table) {
            $table->text("description_text")->nullable()->default('')->after('description');
        });

        foreach (Fund::get() as $fund) {
            $fund->update([
                'description_text' => $fund->descriptionToText(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('funds', function (Blueprint $table) {
            $table->dropColumn("description_text");
        });
    }
}
