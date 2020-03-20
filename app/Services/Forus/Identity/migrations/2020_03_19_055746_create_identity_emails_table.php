<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Services\Forus\Identity\Models\Identity;
use App\Services\Forus\Record\Models\Record;

class CreateIdentityEmailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('identity_emails', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email', 200);
            $table->string('identity_address', 200)->index();
            $table->boolean('verified')->default(0);
            $table->boolean('primary')->default(0)->index();
            $table->string('verification_token', 200);
            $table->softDeletes();
            $table->timestamps();

            $table->index([
                'identity_address', 'primary'
            ]);

            $table->foreign('identity_address'
            )->references('address')->on('identities')->onDelete('cascade');
        });

        $recordService = resolve('forus.services.record');
        $identities = Identity::get();

        foreach ($identities as $identity) {
            $identity->addEmail(
                Record::query()->where([
                    'record_type_id' => $recordService->getTypeIdByKey('primary_email'),
                    'identity_address' => $identity->address,
                ])->first()->value,
                true,
                true
            );
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('identity_emails');
    }
}
