<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @noinspection PhpUnused
 */
class DropBunqMeTabsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::dropIfExists('bunq_me_tabs');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::create('bunq_me_tabs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('bunq_me_tab_id');
            $table->integer('monetary_account_id');
            $table->unsignedInteger('fund_id');
            $table->string('status', 24);
            $table->decimal('amount');
            $table->string('description', 400)->default('');
            $table->string('uuid', 64);
            $table->string('share_url', 200);
            $table->string('issuer_authentication_url', 200)->nullable();
            $table->timestamp('last_check_at')->nullable();
            $table->timestamps();

            $table->foreign('fund_id')
                ->references('id')->on('funds')
                ->onDelete('cascade');
        });
    }
}
