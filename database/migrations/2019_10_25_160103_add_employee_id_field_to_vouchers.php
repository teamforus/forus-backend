<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Voucher;

class AddEmployeeIdFieldToVouchers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vouchers', function(Blueprint $table) {
            $table->unsignedInteger('employee_id')->nullable()->after('note');
            $table->foreign('employee_id'
            )->references('id')->on('employees')->onDelete('set null');
        });

        Voucher::whereNull('parent_id')->where(function(Builder $query) {
            $vouchers = $query->whereNotNull('note')
                ->orWhereRaw('`created_at` != `updated_at`')
                ->orWhereNull('identity_address')->get();

            $vouchers->each(function(Voucher $voucher) {
                $employees = $voucher->fund->organization->employees();
                $voucher->update([
                    'employee_id' => $employees->whereHas('roles', function(Builder $query) {
                        return $query->where('key', 'validation');
                    })->first()->id ?? null
                ]);
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('vouchers', function(Blueprint $table) {
            $table->dropForeign('vouchers_employee_id_foreign');
            $table->dropColumn('employee_id');
        });
    }
}
