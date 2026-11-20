<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * @return void
     */
    public function up(): void
    {
        Schema::create('dcp_connections', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('implementation_id');
            $table->enum('environment', ['sandbox', 'production']);
            $table->boolean('enabled')->default(false);
            $table->string('username', 200)->nullable();
            $table->text('password')->nullable();
            $table->timestamps();

            $table->unique(['implementation_id', 'environment'], 'dcp_connections_implementation_environment_unique');

            $table->foreign('implementation_id', 'dcp_connections_implementation_fk')
                ->references('id')->on('implementations')->restrictOnDelete();
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('dcp_connections');
    }
};
