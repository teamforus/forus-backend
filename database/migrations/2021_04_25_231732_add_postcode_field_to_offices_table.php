<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Office;

/**
 * @noinspection PhpUnused
 */
class AddPostcodeFieldToOfficesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->string('postcode', 15)->nullable()->after('lat');
            $table->string('postcode_number', 15)->nullable()->after('postcode');
            $table->string('postcode_addition', 15)->nullable()->after('postcode_number');
        });

        /** @var Office[] $offices */
        $offices = Office::whereNull('postcode')->get();

        foreach ($offices as $office) {
            $office->updateGeoData();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->dropColumn('postcode', 'postcode_number', 'postcode_addition');
        });
    }
}
