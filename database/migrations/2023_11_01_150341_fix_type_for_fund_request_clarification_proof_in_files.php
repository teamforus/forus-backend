<?php

use App\Models\FundRequestClarification;
use Illuminate\Database\Migrations\Migration;
use App\Services\FileService\Models\File;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        File::query()
            ->where('type', 'fund_request_record_proof')
            ->where('fileable_type', (new FundRequestClarification())->getMorphClass())
            ->update([
                'type' => 'fund_request_clarification_proof'
            ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void {}
};
