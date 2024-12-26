<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('email_log_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('email_log_id');
            $table->enum('type', ['raw', 'attachment']);
            $table->string('path', 200);
            $table->string('file_name', 1000);
            $table->string('content_id', 200)->nullable();
            $table->string('content_type', 200);

            $table->foreign('email_log_id')
                ->references('id')
                ->on('email_logs')
                ->onDelete('restrict');

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
        Schema::dropIfExists('email_log_attachments');
    }
};
