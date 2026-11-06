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
        Schema::create('decision_trees', function (Blueprint $table) {
            $table->id();
            $table->integer('implementation_id')->unsigned();
            $table->integer('organization_id')->unsigned()->nullable();
            $table->string('slug')->unique();
            $table->string('name');
            $table->boolean('is_common')->default(false);
            $table->string('start_node_key');
            $table->boolean('is_active')->default(true);
            $table->string('application_url', 1000)->nullable();
            $table->timestamps();

            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->onDelete('cascade');

            $table->foreign('implementation_id')
                ->references('id')
                ->on('implementations')
                ->onDelete('cascade');
        });

        Schema::create('decision_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('decision_tree_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->enum('type', ['decision', 'category', 'result']);
            $table->text('label');
            $table->text('description')->nullable();
            $table->boolean('eligible')->nullable();
            $table->boolean('show_button')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['decision_tree_id', 'key']);
        });

        Schema::create('decision_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('decision_node_id')->constrained('decision_nodes')->cascadeOnDelete();
            $table->string('label');
            $table->string('value');
            $table->string('next_node_key');
            $table->foreignId('activates_tree_id')->nullable()->constrained('decision_trees')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['decision_node_id', 'value']);
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('decision_options');
        Schema::dropIfExists('decision_nodes');
        Schema::dropIfExists('decision_trees');
    }
};
