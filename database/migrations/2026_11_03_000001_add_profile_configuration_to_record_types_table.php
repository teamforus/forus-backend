<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * @return void
     */
    public function up(): void
    {
        Schema::table('record_types', function (Blueprint $table) {
            $table->boolean('profile_records')->default(false)->after('requester_imports');
            $table->boolean('profile_requester_editable')->default(false)->after('profile_records');
            $table->unsignedBigInteger('profile_record_group_id')->nullable();
            $table->unsignedInteger('profile_sort_order')->default(0);

            $table->enum('validation_key', [
                'person_name', 'birth_date', 'short_text', 'city_name', 'street_name',
                'house_number', 'house_addition', 'postcode', 'name_part', 'bsn', 'house_letter',
            ])->nullable()->default(null)->after('profile_requester_editable');

            $table->foreign('profile_record_group_id', 'record_types_profile_record_group_id_foreign')
                ->references('id')
                ->on('profile_record_groups')
                ->nullOnDelete();
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('record_types', function (Blueprint $table) {
            $table->dropForeign('record_types_profile_record_group_id_foreign');
            $table->dropColumn('profile_record_group_id');
            $table->dropColumn('profile_sort_order');
            $table->dropColumn(['profile_records', 'profile_requester_editable', 'validation_key']);
        });
    }
};
