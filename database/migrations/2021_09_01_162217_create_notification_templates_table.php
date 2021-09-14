<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     * @throws Exception
     */
    public function up()
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('key', 200);
            $table->string('type', 20)->default('mail');
            $table->boolean('formal')->default(false);
            $table->unsignedInteger('implementation_id');
            $table->string('title', 400)->default('');
            $table->string('content', 16384)->default('');
            $table->timestamps();

            $table->unique([
                'key', 'type', 'formal', 'implementation_id',
            ], 'notification_templates_fields_unique');

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
    public function down()
    {
        Schema::dropIfExists('notification_templates');
    }
}
