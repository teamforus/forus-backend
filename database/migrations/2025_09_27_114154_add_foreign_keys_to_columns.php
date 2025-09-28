<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fund_provider_invitations', function (Blueprint $table) {
            $table->foreign('fund_id')
                ->references('id')
                ->on('funds')
                ->onDelete('cascade');

            $table->foreign('from_fund_id')
                ->references('id')
                ->on('funds')
                ->onDelete('cascade');

            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->onDelete('cascade');
        });

        Schema::table('digid_sessions', function (Blueprint $table) {
            $table->foreign('implementation_id')
                ->references('id')
                ->on('implementations')
                ->onDelete('cascade');
        });

        Schema::table('fund_provider_products', function (Blueprint $table) {
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('restrict');
        });

        Schema::table('fund_request_records', function (Blueprint $table) {
            $table->foreign('fund_criterion_id')
                ->references('id')
                ->on('fund_criteria')
                ->onDelete('restrict');
        });

        Schema::table('implementation_pages', function (Blueprint $table) {
            $table->foreign('implementation_id')
                ->references('id')
                ->on('implementations')
                ->onDelete('cascade');
        });

        Schema::table('implementations', function (Blueprint $table) {
            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->onDelete('set null');
        });

        Schema::table('prevalidations', function (Blueprint $table) {
            $table->dropIndex(['fund_id']);

            $table->foreign('fund_id')
                ->references('id')
                ->on('funds')
                ->onDelete('cascade');

            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->onDelete('cascade');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreign('sponsor_organization_id')
                ->references('id')
                ->on('organizations')
                ->onDelete('set null');
        });

        Schema::table('profile_bank_accounts', function (Blueprint $table) {
            $table->foreign('profile_id')
                ->references('id')
                ->on('profiles')
                ->onDelete('cascade');
        });

        Schema::table('record_validations', function (Blueprint $table) {
            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->onDelete('cascade');
        });

        Schema::table('sessions', function (Blueprint $table) {
            $table->dropIndex(['identity_proxy_id']);

            $table->foreign('identity_proxy_id')
                ->references('id')
                ->on('identity_proxies')
                ->onDelete('cascade');
        });

        Schema::table('voucher_transactions', function (Blueprint $table) {
            $table->foreign('fund_provider_product_id')
                ->references('id')
                ->on('fund_provider_products')
                ->onDelete('set null');
        });

        // identity address
        Schema::table('employees', function (Blueprint $table) {
            $table->foreign('identity_address')
                ->references('address')
                ->on('identities')
                ->onDelete('cascade');
        });

        Schema::table('digid_sessions', function (Blueprint $table) {
            $table->foreign('identity_address')
                ->references('address')
                ->on('identities')
                ->onDelete('cascade');
        });

        Schema::table('event_logs', function (Blueprint $table) {
            $table->foreign('identity_address')
                ->references('address')
                ->on('identities')
                ->onDelete('set null');
        });

        Schema::table('physical_cards', function (Blueprint $table) {
            $table->foreign('identity_address')
                ->references('address')
                ->on('identities')
                ->onDelete('cascade');
        });

        Schema::table('sessions', function (Blueprint $table) {
            $table->foreign('identity_address')
                ->references('address')
                ->on('identities')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fund_provider_invitations', function (Blueprint $table) {
            $table->dropForeign(['fund_id']);
            $table->dropForeign(['from_fund_id']);
            $table->dropForeign(['organization_id']);
        });

        Schema::table('digid_sessions', function (Blueprint $table) {
            $table->dropForeign(['implementation_id']);
        });

        Schema::table('fund_provider_products', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });

        Schema::table('fund_request_records', function (Blueprint $table) {
            $table->dropForeign(['fund_criterion_id']);
        });

        Schema::table('implementation_pages', function (Blueprint $table) {
            $table->dropForeign(['implementation_id']);
        });

        Schema::table('implementations', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
        });

        Schema::table('prevalidations', function (Blueprint $table) {
            $table->dropForeign(['fund_id']);
            $table->dropForeign(['organization_id']);
            $table->index(['fund_id']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['sponsor_organization_id']);
        });

        Schema::table('profile_bank_accounts', function (Blueprint $table) {
            $table->dropForeign(['profile_id']);
        });

        Schema::table('record_validations', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
        });

        Schema::table('sessions', function (Blueprint $table) {
            $table->dropForeign(['identity_proxy_id']);
            $table->index(['identity_proxy_id']);
        });

        Schema::table('voucher_transactions', function (Blueprint $table) {
            $table->dropForeign(['fund_provider_product_id']);
        });

        // identity address
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['identity_address']);
        });

        Schema::table('digid_sessions', function (Blueprint $table) {
            $table->dropForeign(['identity_address']);
        });

        Schema::table('event_logs', function (Blueprint $table) {
            $table->dropForeign(['identity_address']);
        });

        Schema::table('physical_cards', function (Blueprint $table) {
            $table->dropForeign(['identity_address']);
        });

        Schema::table('sessions', function (Blueprint $table) {
            $table->dropForeign(['identity_address']);
        });
    }
};
