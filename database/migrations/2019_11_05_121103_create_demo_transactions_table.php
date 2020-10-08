<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDemoTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('demo_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('token', 100);
            $table->enum('state', [
                'pending', 'accepted', 'rejected'
            ]);
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
        Schema::dropIfExists('demo_transactions');
    }
}
