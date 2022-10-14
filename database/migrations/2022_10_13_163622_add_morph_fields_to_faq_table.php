<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Faq;
use App\Services\MediaService\Models\Media;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('faq', function (Blueprint $table) {
            $table->dropForeign('fund_faq_fund_id_foreign');
            $table->renameColumn('fund_id', 'faq_id');
        });

        Schema::table('faq', function (Blueprint $table) {
            $table->string('faq_type')->default('')->after('faq_id');
        });

        Faq::query()->update([
            'faq_type' => 'fund',
        ]);

        Media::where([
            'mediable_type' => 'fund_faq',
        ])->update([
            'mediable_type' => 'faq',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Faq::where('faq_type', '!=', 'fund')->delete();

        Schema::table('faq', function (Blueprint $table) {
            $table->foreign('faq_id', 'fund_faq_fund_id_foreign')
                ->references('id')->on('funds')
                ->onDelete('cascade');

            $table->renameColumn('faq_id', 'fund_id');
            $table->dropColumn('faq_type');
        });

        Media::where([
            'mediable_type' => 'faq',
        ])->update([
            'mediable_type' => 'fund_faq',
        ]);
    }
};
