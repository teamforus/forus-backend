<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * App\Models\VoucherToken
 *
 * @property int $id
 * @property int $voucher_id
 * @property string $address
 * @property int $need_confirmation
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Voucher $voucher
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VoucherToken newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VoucherToken newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VoucherToken query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VoucherToken whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VoucherToken whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VoucherToken whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VoucherToken whereNeedConfirmation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VoucherToken whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VoucherToken whereVoucherId($value)
 * @mixin \Eloquent
 */
class VoucherToken extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'voucher_id', 'address', 'need_confirmation'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function voucher() {
        return $this->belongsTo(Voucher::class);
    }

    /**
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    public function getQrCodeStorage () {
        return  resolve('filesystem')->disk(
            env('VOUCHER_QR_STORAGE_DRIVER', 'public')
        );
    }

    public function storeQrCodeFile() {
        $storage = $this->getQrCodeStorage();
        $qrCode = QrCode::format('png')->size(400)->margin(2);

        $storage->put($this->qrCodeFilePath(), $qrCode->generate(json_encode([
            "type"  => "voucher",
            "value" => $this->address
        ])), 'public');
    }

    /**
     * @return bool
     */
    public function qrCodeFileExists() {
        return $this->getQrCodeStorage()->exists($this->qrCodeFilePath());
    }

    public function getQrCodeUrl () {
        $storage = $this->getQrCodeStorage();
        $path = $this->qrCodeFilePath();

        if (!$storage->exists($path)) {
            $this->storeQrCodeFile();
        }

        return $storage->url($path);
    }

    /**
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function getQrCodeFile () {
        $storage = $this->getQrCodeStorage();
        $path = $this->qrCodeFilePath();

        if (!$storage->exists($path)) {
            $this->storeQrCodeFile();
        }

        return $storage->get($path);
    }

    private function qrCodeFilePath() {
        return sprintf(
            "vouchers/qr-codes/%s.png",
            hash('sha256', $this->address));
    }
}
