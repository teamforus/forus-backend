<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Fund;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        DB::getDoctrineSchemaManager()
            ->getDatabasePlatform()
            ->registerDoctrineTypeMapping('enum', 'string');

        Schema::table('funds', function(Blueprint $table) {
            $table->string('description_short', 1000)->nullable()->after('description_text');
            $table->string('description', 4000)->nullable()->change();
        });

        foreach (Fund::get() as $fund) {
            $fund->update([
                'description' => null,
                'description_text' => '',
                'description_short' => strip_tags($fund->descriptionToHtml()),
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
            $table->dropColumn('description_short');
        });
    }
};
