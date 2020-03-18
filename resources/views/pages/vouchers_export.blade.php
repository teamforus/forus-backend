<?php
    /** @var \App\Models\Voucher $voucher */
?>

@foreach($vouchers as $voucher)
    @php
        $token_generator = resolve('token_generator');
        $name = $token_generator->generate(6, 2);
        $amount = $voucher->amount;

        $qr_code = make_qr_code(
            'voucher',
            $voucher->token_without_confirmation->address
        );

        fputcsv($fp, [$name, $amount]);
    @endphp

    <div style="height: 50%">
        <img width="400" alt="qr-code" src="data:image/png;base64, {!! base64_encode($qr_code) !!} "/>

        <div style="position: absolute; bottom: 0; left: 0;">
            <span>{{ $name }} - </span>
            <span>{{ $amount }}€</span>
        </div>
    </div>
@endforeach
