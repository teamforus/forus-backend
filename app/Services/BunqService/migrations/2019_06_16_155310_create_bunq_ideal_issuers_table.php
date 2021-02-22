<?php /** @noinspection PhpIllegalPsrClassPathInspection */

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBunqIdealIssuersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bunq_ideal_issuers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 200);
            $table->string('bic', 200);
            $table->boolean('sandbox');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bunq_ideal_issuers');
    }
}
