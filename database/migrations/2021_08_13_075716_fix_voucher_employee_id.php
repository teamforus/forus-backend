<?php

use App\Models\Voucher;
use App\Services\EventLogService\Interfaces\IEventLogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    protected mixed $logService;

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
        /**
         * @var Voucher[]|Collection $vouchers
         * $vouchers = Voucher::whereNotNull('employee_id')->get()->load([
         * 'employee' => function($builder) {
         * /** @var Builder|SoftDeletes $builder
         * $builder->withTrashed();
         * },
         * 'fund.organization.employees' => function($builder) {
         * /** @var Builder|SoftDeletes $builder
         * $builder->withTrashed();
         * },
         * ]);
         *
         * foreach ($vouchers as $voucher) {
         * $this->migrateVoucher($voucher);
         * }
         */
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
    }

    /**
     * @param Voucher $voucher
     */
    protected function migrateVoucher(Voucher $voucher): void
    {
        $employee = $voucher->employee;
        $employees = $voucher->fund->organization->employees;
        $organization = $voucher->fund->organization;

        if ($employee) {
            if ($employee->organization_id != $organization->id) {
                $employees = $employees->where('identity_address', $employee->identity_address);

                if ($employees->count() > 0) {
                    $employee = $employees->first();
                    $voucher->employee()->associate($employees->first())->save();
                } else {
                    echo "Could not migrate voucher: $voucher->id\n";
                }
            }

            $employeeMeta = collect($this->logService->modelToMeta('employee', $employee));
            $employeeMeta = $employeeMeta->mapWithKeys(function ($value, $key) {
                return ['data->' . $key => $value];
            })->toArray();

            $voucher->logs()->whereIn('event', Voucher::EVENTS_CREATED)->update($employeeMeta);
        } else {
            echo "Could not find the employee for voucher: $voucher->id\n";
        }
    }
};
