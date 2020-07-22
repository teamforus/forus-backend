<?php
// styles
$baseFont = "Montserrat, Helvetica, Arial, sans-serif;";

$wrapperStyle = "padding: 30px 0; background-color: #F6F5F5; position: relative; background-repeat: no-repeat; background-position: 100% 0;";
$emailInnerStyle = "position: relative; z-index: 1; width: 650px; margin: auto; max-width: 100%;";
$emailBodyStyle  = "padding: 35px 35px 35px; border-bottom: 3px solid #dfe4ec; position: relative;  border: 1px solid #efefef; background-color: #fff; border-radius: 8px;";
$emailFooterStyle = "color: #9397A3; font: 400 12px/20px $baseFont; text-align: center; padding: 10px 5px 10px; cursor: default;";
$emailFooterLinkStyle = "color: #315EFD; text-decoration: underline; font: inherit;";

$textCenter = "text-align: center;";

$h1Style = "font: 700 32px/38px $baseFont color: #1e1e1e; margin: 0 0 25px; cursor: default;";
$h2Style = "font: 700 25px/32px $baseFont color: #1e1e1e; margin: 0 0 20px; cursor: default;";
$h3Style = "font: 700 21px/28px $baseFont color: #1e1e1e; margin: 0 0 15px; cursor: default;";
$h4Style = "font: 700 18px/24px $baseFont color: #1e1e1e; margin: 0 0 10px; cursor: default;";
$h5Style = "font: 700 16px/20px $baseFont color: #1e1e1e; margin: 0 0 10px; cursor: default;";

$textStyle = "margin: 0 0 15px; font: 400 16px/28px $baseFont color: #383D45; cursor: default;";
$textMutedStyle = $textStyle . "font-size: 14px; line-height: 22px; color: #646f79; cursor: default;";

$btnStyle = "display: inline-block; padding: 5px 75px; font: 600 14px/40px $baseFont color: #fff; border-radius: 3px; text-decoration: none;";
$btnDangerStyle = $btnStyle . "background-color: #bc2527;";
$btnSuccessStyle = $btnStyle . "background-color: #74c86b;";
$btnPrimaryStyle = $btnStyle . "background-color: #315EFD;";

$sectionStyle = "margin: 0 0 10px;";
$ellipsisStyle = "display: block; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;";
$marginLessStyle = 'margin-bottom: 0;';
$separatorStyle = "width: 100%; margin: 0 0 25px; height: 1px; border-bottom: 1px solid #dfe4ec; padding-top: 15px;";

$styles = [
    'h1' => $h1Style,
    'h2' => $h2Style,
    'h3' => $h3Style,
    'h4' => $h4Style,
    'h5' => $h5Style,
    'text' => $textStyle,
    'text_center' => $textCenter,
    'margin_less' => $marginLessStyle,
    'space' => $sectionStyle,
    'separator' => $separatorStyle,
    'button_primary' => $btnPrimaryStyle,
    'button_success' => $btnSuccessStyle,
    'button_danger' => $btnDangerStyle,
];

if (isset($emailBody) && is_object($emailBody) && $emailBody instanceof \App\Mail\MailBodyBuilder) {
    $emailBody = $emailBody->toArray();
}


foreach (array_keys($emailBody) as $key) {
    $emailBody[$key]['style'] = (array) $emailBody[$key][0];
    $emailBody[$key]['style'] = array_reduce($emailBody[$key]['style'], static function($str, $item) use ($styles) {
        return $str . $styles[$item];
    }, "");
}

?>
<style>
    body {
        cursor: default;
        background-color: #F6F5F5;
        margin: 0;
        padding: 0;
    }
</style>
<?php
?>
<div class="email-wrapper" style="{{ $wrapperStyle }}">
    <div class="email-inner" style="{{ $emailInnerStyle }}">
        <div class="email-body" style="{{ $emailBodyStyle }}">
            @if (is_array($emailBody))
                @foreach($emailBody as $emailItem)
                    @if(array_intersect((array) $emailItem[0], ['h1', 'h2', 'h3', 'h4', 'h5', 'text']))
                        <div style="{{ $emailItem['style'] ?? '' }}">{!! nl2br(e($emailItem[1] ?? '')) !!}</div>
                    @elseif(array_intersect((array) $emailItem[0], ['space']))
                        <div style="{{ $emailItem['style'] ?? '' }}">&nbsp;</div>
                    @elseif(array_intersect((array) $emailItem[0], ['button_primary', 'button_success', 'button_danger']))
                        <div style="{{ $textCenter }}">
                            <a style="{{ $emailItem['style'] ?? '' }}" href="{{ $emailItem[2] ?? '' }}">{!! nl2br(e($emailItem[1] ?? '')) !!}</a>
                        </div>
                    @elseif(array_intersect((array) $emailItem[0], ['separator']))
                        <div style="{{ $emailItem['style'] ?? '' }}"></div>
                    @endif
                @endforeach
            @endif
        </div>
        <div class="email-footer" style="{{ $emailFooterStyle }}">
            @isset($email)
                {!! mail_trans('not_for_you', ['email' => $email, 'unsubscribeLink' => $unsubscribeLink, 'email_preferences_link' => $notificationPreferencesLink]) !!}
            @endisset
        </div>
    </div>
</div>