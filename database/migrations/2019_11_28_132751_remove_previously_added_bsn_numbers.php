<?php

use App\Models\Record;
use App\Models\RecordType;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @throws Exception
     */
    public function up(): void
    {
        Record::whereRelation('record_type', 'key', '=', 'bsn')->forceDelete();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void {}
};
