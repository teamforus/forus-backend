<?php

use App\Models\Identity;
use App\Models\Record;
use App\Models\RecordType;
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
        Schema::create('identity_emails', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email', 200);
            $table->string('identity_address', 200)->index();
            $table->boolean('verified')->default(0);
            $table->boolean('primary')->default(0)->index();
            $table->boolean('initial')->default(0)->index();
            $table->string('verification_token', 200);
            $table->softDeletes();
            $table->timestamps();

            $table->index([
                'identity_address', 'primary',
            ]);

            $table->foreign('identity_address')
                ->references('address')
                ->on('identities')
                ->onDelete('cascade');
        });

        $identities = Identity::get();

        foreach ($identities as $identity) {
            $identity->addEmail(
                Record::where([
                    'record_type_id' => RecordType::findByKey('primary_email')->id,
                    'identity_address' => $identity->address,
                ])->first()->value,
                true,
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
    public function down(): void
    {
        Schema::dropIfExists('identity_emails');
    }
};
