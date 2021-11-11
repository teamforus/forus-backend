<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSystemNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('system_notifications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('key', 200);
            $table->boolean('mail')->default(0);
            $table->boolean('push')->default(0);
            $table->boolean('database')->default(1);
            $table->boolean('optional')->default(0);
            $table->boolean('visible')->default(0);
            $table->boolean('editable')->default(0);
            $table->unsignedInteger('order')->default(0);
            $table->string('group', 40)->nullable()->default(null);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('system_notifications');
    }
}
