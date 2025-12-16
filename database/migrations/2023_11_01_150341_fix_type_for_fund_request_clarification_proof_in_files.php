<?php

use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        DB::table('files')
            ->where('type', 'fund_request_record_proof')
            ->where('fileable_type', 'fund_request_clarification')
            ->update([
                'type' => 'fund_request_clarification_proof',
            ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
    }
};
