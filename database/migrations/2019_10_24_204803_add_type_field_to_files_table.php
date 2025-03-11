<?php

use App\Services\FileService\Models\File;
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
        if (!Schema::hasColumn('files', 'type')) {
            Schema::table('files', function (Blueprint $table) {
                $table->string('type', 60)->default('')->after('original_name');
            });

            File::where('type', '=', '')->update([
                'type' => 'fund_request_record_proof',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        //
    }
};
