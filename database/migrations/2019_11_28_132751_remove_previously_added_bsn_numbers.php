<?php

use Illuminate\Database\Migrations\Migration;

/**
 * @noinspection PhpUnused
 */
class RemovePreviouslyAddedBsnNumbers extends Migration
{
    /**
     * Run the migrations.
     *
     * @throws Exception
     */
    public function up(): void
    {
        $recordRepo = resolve('forus.services.record');

        App\Services\Forus\Record\Models\Record::where([
            'record_type_id' => $recordRepo->getTypeIdByKey('bsn')
        ])->forceDelete();
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
}
