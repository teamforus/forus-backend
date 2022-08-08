<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Organization;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Organization::query()->update([
            'iban' => DB::raw('UPPER(`iban`)'),
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void {}
};
