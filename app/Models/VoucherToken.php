<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Class VoucherToken
 * @property mixed $id
 * @property mixed $voucher_id
 * @property string $address
 * @property boolean $need_confirmation
 * @property Voucher $voucher
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @package App\Models
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

    public function storeQrCodeFile() {
        /** @var \Storage $storage */
        $storage = app()->make('filesystem')->disk(
            env('VOUCHER_QR_STORAGE_DRIVER', 'public')
        );

        $qrCode = QrCode::format('png')->size(400)->margin(2);

        $storage->put($this->qrCodeFilePath(), $qrCode->generate(json_encode([
            "type"  => "voucher",
            "value" => $this->address
        ])), 'public');
    }

    public function getQrCodeUrl () {
        /** @var \Storage $storage */
        $storage = app()->make('filesystem')->disk(
            env('VOUCHER_QR_STORAGE_DRIVER', 'public')
        );

        $path = $this->qrCodeFilePath();

        if (!$storage->exists($path)) {
            $this->storeQrCodeFile();
        }

        return $storage->url($path);
    }

    private function qrCodeFilePath() {
        return sprintf(
            "vouchers/qr-codes/%s.png",
            hash('sha256', $this->address));
    }
}
