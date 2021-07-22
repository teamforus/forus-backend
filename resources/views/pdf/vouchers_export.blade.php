<?php
/** @var \App\Models\Data\VoucherExportData[] $vouchersData */
?>
<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
</head>
<style>
    @page {
        margin: 100px 25px 0 25px;
    }
</style>
<body style="padding: 30px 30px 30px 30px; margin: 0 0 0 0;">
    @foreach($vouchersData as $voucherData)
        @php
            $qr_code = make_qr_code('voucher', $voucherData->getVoucher()->token_without_confirmation->address);
        @endphp

        <div style="height: 50%">
            <img width="400" style="border: 1px solid silver;" alt="qr-code" src="data:image/png;base64, {!! base64_encode($qr_code) !!} "/>

            @if ($voucherData->getVoucher()->fund->type == \App\Models\Fund::TYPE_BUDGET)
                <div style="position: absolute; bottom: 0; left: 0;">
                    <span>{{ $voucherData->getName() }} - </span>
                    <span>{{ $voucherData->getVoucher()->amount }}€</span>
                </div>
            @endif
        </div>
    @endforeach

</body>
</html>