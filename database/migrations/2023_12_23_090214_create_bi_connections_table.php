<?php

use App\Models\Organization;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('bi_connections', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('organization_id');
            $table->enum('auth_type', ['disabled', 'header', 'parameter'])
                ->default('disabled');

            $table->string('access_token', 200);
            $table->string('expiration_period', 10);

            $table->json('data_types')->nullable();
            $table->json('ips')->nullable();

            $table->timestamp('expire_at');
            $table->timestamps();

            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->onDelete('cascade');
        });

        $organizations = Organization::where('allow_bi_connection', true)->get();
        $dataTypes = [
            'vouchers', 'fund_identities', 'reimbursements', 'employees', 'funds', 'funds_detailed',
            'fund_providers', 'fund_provider_finances', 'voucher_transactions', 'voucher_transaction_bulks',
        ];

        foreach ($organizations as $organization) {
            DB::table('bi_connections')->insert([
                'auth_type' => $organization->bi_connection_auth_type ?? 'disabled',
                'access_token' => !empty($organization->bi_connection_token)
                    ? $organization->bi_connection_token
                    : token_generator()->generate(64),
                'organization_id' => $organization->id,
                'expiration_period' => '1_month',
                'expire_at' => now()->addMonth(),
                'data_types' => json_encode($dataTypes),
                'ips' => json_encode([]),
                'updated_at' => now(),
                'created_at' => now(),
            ]);
        }

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['bi_connection_auth_type', 'bi_connection_token']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->enum('bi_connection_auth_type', ['disabled', 'header', 'parameter'])
                ->default('disabled')
                ->after('provider_throttling_value');

            $table->string('bi_connection_token', 200)
                ->after('bi_connection_auth_type');
        });

        Schema::dropIfExists('bi_connections');
    }
};
