<?php

use App\Services\EventLogService\Interfaces\IEventLogService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Voucher;

/**
 * @noinspection PhpUnused
 */
class FixVoucherEmployeeId extends Migration
{
    protected $logService;

    public function __construct()
    {
        $this->logService = resolve(IEventLogService::class);
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        /** @var Voucher[]|Collection $vouchers */
        $vouchers = Voucher::whereNotNull('employee_id')->with(
            'employee', 'fund.organization.employees'
        )->get();

        foreach ($vouchers as $voucher) {
            $this->migrateVoucher($voucher);
        }
    }

    protected function migrateVoucher(Voucher $voucher): void
    {
        $employee = $voucher->employee;
        $employees = $voucher->fund->organization->employees;
        $organization = $voucher->fund->organization;

        if ($employee->organization_id !=  $organization->id) {
            $employees = $employees->where('identity_address', $employee->identity_address);
            $voucher->employee()->associate($employees)->save();
        }

        $employeeMeta = collect($this->logService->modelToMeta('employee', $employee));
        $employeeMeta = $employeeMeta->mapWithKeys(function($value, $key) {
            return ['data->' . $key => $value];
        })->toArray();

        $voucher->logs()->whereIn('event', Voucher::EVENTS_CREATED)->update($employeeMeta);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void {}
}
