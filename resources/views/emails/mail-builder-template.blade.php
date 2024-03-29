<?php

$styles = config('forus.mail_styles');

if (isset($emailBody) && $emailBody instanceof \App\Mail\MailBodyBuilder) {
    $emailBody = $emailBody->toArray();
} else {
    return;
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
<div class="email-wrapper" style="{{ $styles['wrapper'] }}">
    <div class="email-inner" style="{{ $styles['inner'] }}">
        <div class="email-body" style="{{ $styles['body'] }}">
            @if (is_array($emailBody))
                @foreach($emailBody as $emailItem)
                    @if(array_intersect((array) $emailItem[0], ['h1', 'h2', 'h3', 'h4', 'h5', 'text']))
                        <div style="{{ $emailItem['style'] ?? '' }}">{!! nl2br(e($emailItem[1] ?? '')) !!}</div>
                    @elseif(array_intersect((array) $emailItem[0], ['space']))
                        <div style="{{ $emailItem['style'] ?? '' }}">&nbsp;</div>
                    @elseif(array_intersect((array) $emailItem[0], ['button_primary', 'button_success', 'button_danger']))
                        <div style="{{ $styles['text_center'] }}">
                            <a style="{{ $emailItem['style'] ?? '' }}" href="{{ $emailItem[2] ?? '' }}">{!! nl2br(e($emailItem[1] ?? '')) !!}</a>
                        </div>
                    @elseif(array_intersect((array) $emailItem[0], ['link']))
                        <div style="{{ $styles['link_block'] }}">
                            <a style="{{ $emailItem['style'] ?? '' }}" href="{{ $emailItem[2] ?? '' }}">{!! nl2br(e($emailItem[1] ?? '')) !!}</a>
                        </div>
                    @elseif(array_intersect((array) $emailItem[0], ['separator']))
                        <div style="{{ $emailItem['style'] ?? '' }}"></div>
                    @elseif(array_intersect((array) $emailItem[0], ['markdown']))
                        <div style="{{ $emailItem['style'] ?? '' }}">{!! $emailItem[1] !!}</div>
                    @endif
                @endforeach
            @endif
        </div>
        @if (!($hideFooter ?? false))
            <div class="email-footer" style="{{ $styles['email_footer'] }}">
                @isset($email)
                    Deze e-mail is verstuurd naar {{ $email }}. Is deze mail niet aan u gericht? Klik dan <a href="{{ $unsubscribeLink }}">hier</a>.
                    @if (isset($notificationPreferencesLink) && $notificationPreferencesLink)
                        Is deze mail wel aan u gericht maar wilt u zich afmelden voor dit soort berichten?
                        U kunt dit aangeven in uw <a href="{{ $notificationPreferencesLink }}">e-mailvoorkeuren</a>.
                    @endif
                @endisset
            </div>
        @endif
    </div>
</div>