<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @noinspection PhpUnused
 */
class CreateSystemNotificationConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('system_notification_configs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('system_notification_id');
            $table->unsignedInteger('implementation_id');
            $table->boolean('enable_all')->default(1);
            $table->boolean('enable_mail')->default(1);
            $table->boolean('enable_push')->default(1);
            $table->boolean('enable_database')->default(1);
            $table->timestamps();

            $table->unique([
                'implementation_id', 'system_notification_id',
            ], 'system_notification_configs_unique_keys');

            $table->index([
                'implementation_id', 'system_notification_id',
            ], 'system_notification_configs_index_keys');

            $table->foreign('system_notification_id')
                ->references('id')->on('system_notifications')
                ->onDelete('cascade');

            $table->foreign('implementation_id')
                ->references('id')->on('implementations')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('system_notification_configs');
    }
}
