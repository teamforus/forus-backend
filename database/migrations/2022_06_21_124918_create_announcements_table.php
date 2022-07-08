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
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['warning', 'danger', 'success', 'primary', 'default'])->default('danger');
            $table->string('title', 2000);
            $table->text('description')->nullable();
            $table->dateTime('expire_at')->nullable();
            $table->enum('scope', ['dashboards', 'sponsor', 'provider', 'validator'])->default('sponsor');;
            $table->boolean('active')->default(true);
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
        Schema::dropIfExists('announcements');
    }
};
