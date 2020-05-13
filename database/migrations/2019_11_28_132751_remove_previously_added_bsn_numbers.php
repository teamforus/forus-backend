<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemovePreviouslyAddedBsnNumbers extends Migration
{
    /**
     * Run the migrations.
     *
     * @throws Exception
     */
    public function up()
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
    public function down()
    {
        //
    }
}
