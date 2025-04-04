<?php

use App\Models\Fund;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fund_forms', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->unsignedInteger('fund_id');
            $table->timestamps();

            $table->foreign('fund_id')
                ->references('id')
                ->on('funds')
                ->onDelete('cascade');
        });

        $funds = Fund::query()
            ->whereDoesntHave('fund_form')
            ->whereRelation('fund_config', 'is_configured', true)
            ->whereHas('criteria')
            ->get();

        foreach ($funds as $key => $fund) {
            $fund->fund_form()->create([
                ...$key === 0 ? ['id' => pow(2, 16) * 2] : [],
                'name' => $fund->name,
                'created_at' => $fund->start_date,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fund_forms');
    }
};
