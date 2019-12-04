<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Services\FileService\Models\File;
use App\Models\Fund;
use App\Models\Office;
use App\Models\Voucher;
use App\Models\Product;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\ProductCategory;
use App\Services\MediaService\Models\Media;

class UpdateMorphableTypes extends Migration
{
    private $morphMap = [
        'fund'              => Fund::class,
        'media'             => Media::class,
        'office'            => Office::class,
        'voucher'           => Voucher::class,
        'product'           => Product::class,
        'employees'         => Employee::class,
        'organization'      => Organization::class,
        'product_category'  => ProductCategory::class,
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach ($this->morphMap as $morphKey => $morphClass) {
            Media::where([
                'mediable_type' => $morphClass
            ])->update([
                'mediable_type' => $morphKey
            ]);

            File::where([
                'fileable_type' => $morphClass
            ])->update([
                'fileable_type' => $morphKey
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        foreach ($this->morphMap as $morphKey => $morphClass) {
            Media::where([
                'mediable_type' => $morphKey
            ])->update([
                'mediable_type' => $morphClass
            ]);

            File::where([
                'fileable_type' => $morphKey
            ])->update([
                'fileable_type' => $morphClass
            ]);
        }
    }
}
