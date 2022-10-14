<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Faq;

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
            $table->unsignedInteger('faq_id')->after('description');
            $table->string('faq_type')->after('faq_id');
        });

        foreach(Faq::all() as $faq) {
            $faq->update([
                'faq_id'   => $faq->fund_id,
                'faq_type' => 'fund',
            ]);
        }

        Schema::table('faq', function (Blueprint $table) {
            $table->dropForeign('fund_faq_fund_id_foreign');
            $table->dropColumn('fund_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('faq', function (Blueprint $table) {
            $table->unsignedInteger('fund_id')->after('id');

            $table->foreign('fund_id')
                ->references('id')->on('funds')
                ->onDelete('cascade');

            $table->dropColumn('faq_id');
            $table->dropColumn('faq_type');
        });
    }
};
