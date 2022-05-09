<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Database\Seeders\NotificationTemplatesTableSeeder;

/**
 * @noinspection PhpUnused
 */
class CreateNotificationTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     * @throws Exception
     */
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('type', 20)->default('mail');
            $table->boolean('formal')->default(false);
            $table->unsignedBigInteger('system_notification_id');
            $table->unsignedInteger('implementation_id');
            $table->string('title', 400)->default('');
            $table->string('content', 16384)->default('');
            $table->timestamps();

            $table->unique([
                'type', 'formal', 'system_notification_id', 'implementation_id',
            ], 'notification_templates_fields_unique');

            $table->foreign('system_notification_id')
                ->references('id')->on('system_notifications')
                ->onDelete('cascade');

            $table->foreign('implementation_id')
                ->references('id')->on('implementations')
                ->onDelete('cascade');
        });

        (new NotificationTemplatesTableSeeder())->run();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
}
