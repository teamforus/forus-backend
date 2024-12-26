<?php
/** @var \App\Services\MailDatabaseLoggerService\Models\EmailLog $emailLog */
?>
<div style="padding: 0 10px">
    <div style="width: 100%; background: #fff; padding: 20px 0; border: 1px solid #e5e5e5;">
        <div style="width: 650px; margin: 0 auto; border-radius: 4px;">
            <div style="margin: 0 0 10px">
                <div style="font: 700 12px/16px Verdana, Arial, sans-serif">Titel</div>
                <div style="font: 400 12px/16px Verdana, Arial, sans-serif">{{ $emailLog->subject }}</div>
            </div>
            <div style="margin: 0 0 10px">
                <div style="font: 700 12px/16px Verdana, Arial, sans-serif">Verstuurd op</div>
                <div style="font: 400 12px/16px Verdana, Arial, sans-serif">{{ format_datetime_locale($emailLog->created_at) }}</div>
            </div>
            <div style="margin: 0 0 10px">
                <div style="font: 700 12px/16px Verdana, Arial, sans-serif">Ontvanger</div>
                <div style="font: 400 12px/16px Verdana, Arial, sans-serif">{{ $emailLog->to_name }}</div>
                <div style="font: 400 12px/16px Verdana, Arial, sans-serif">{{ $emailLog->to_address }}</div>
            </div>
            <div style="margin: 0 0 10px">
                <div style="font: 700 12px/16px Verdana, Arial, sans-serif">Afzender</div>
                <div style="font: 400 12px/16px Verdana, Arial, sans-serif">{{ $emailLog->from_name }}</div>
                <div style="font: 400 12px/16px Verdana, Arial, sans-serif">{{ $emailLog->from_address }}</div>
            </div>
        </div>
    </div>
</div>