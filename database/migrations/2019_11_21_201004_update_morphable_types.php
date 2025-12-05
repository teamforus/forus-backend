<?php

use App\Models\Employee;
use App\Models\Fund;
use App\Models\Office;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Voucher;
use App\Services\MediaService\Models\Media;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    private array $morphMap = [
        'fund' => Fund::class,
        'media' => Media::class,
        'office' => Office::class,
        'voucher' => Voucher::class,
        'product' => Product::class,
        'employees' => Employee::class,
        'organization' => Organization::class,
        'product_category' => ProductCategory::class,
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        foreach ($this->morphMap as $morphKey => $morphClass) {
            DB::table('media')->where([
                'mediable_type' => $morphClass,
            ])->update([
                'mediable_type' => $morphKey,
            ]);

            DB::table('files')->where([
                'fileable_type' => $morphClass,
            ])->update([
                'fileable_type' => $morphKey,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        foreach ($this->morphMap as $morphKey => $morphClass) {
            DB::table('media')->where([
                'mediable_type' => $morphKey,
            ])->update([
                'mediable_type' => $morphClass,
            ]);

            DB::table('files')->where([
                'fileable_type' => $morphKey,
            ])->update([
                'fileable_type' => $morphClass,
            ]);
        }
    }
};
