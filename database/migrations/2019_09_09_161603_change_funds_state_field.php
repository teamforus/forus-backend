<?php

use App\Models\Fund;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        $fundStates = Fund::pluck('state', 'id');

        foreach ($fundStates as $id => $state) {
            if (!in_array($state, Fund::STATES, true)) {
                exit(sprintf("Can't migrate fund: %s state: %s\n", $id, $state));
            }
        }

        Schema::table('funds', function (Blueprint $table) {
            $table->dropColumn('state');
        });

        Schema::table('funds', function (Blueprint $table) {
            $table->enum('state', Fund::STATES)->default(
                Fund::STATE_WAITING
            )->after('name');
        });

        foreach ($fundStates as $id => $state) {
            Fund::find($id)->update(compact('state'));
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        $fundStates = Fund::query()->pluck('state', 'id');

        Schema::table('funds', function (Blueprint $table) {
            $table->dropColumn('state');
        });

        Schema::table('funds', function (Blueprint $table) {
            $table->string('state', 20)->default('waiting')->after('name');
        });

        foreach ($fundStates as $id => $state) {
            Fund::find($id)->update(compact('state'));
        }
    }
};
