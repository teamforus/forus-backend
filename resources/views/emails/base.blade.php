<?php
    $footerStyle = "border-collapse: collapse; padding-bottom: 25px; font-size: 13px; line-height: 18px; padding-top: 20px;";
?>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    </head>
    <body style="{{ mail_config('base.body_style') }}" bgcolor="{{ mail_config('base.body_bg_color') }}">
    <center>
        <table id="wrapperTable" cellpadding="0" cellspacing="0" border="0" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-size: 0; width: 100% !important; max-width: 600px !important; line-height: 100% !important; background: #fff; margin: 0 auto; padding: 0;" bgcolor="#fff">
            <tr>
                <td valign="top" align="center" style="border-collapse: collapse;">
                    <div id="wrapper" style="font-family: Helvetica, Arial, ArialMT, sans-serif; width: 100%; max-width: 600px; overflow: hidden; color: #2e3238; font-size: 0; background: #f6f5f5; margin: 0 auto;">
                        <table cellpadding="0" cellspacing="0" border="0" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-size: 0; width: 100% !important; text-align: center; background: #fff; margin: 0px auto;" bgcolor="#fff">
                            <tr>
                                <td style="border-collapse: collapse; padding: 24px 24px 32px;">
                                    <h1 style="margin: 0 auto; color: #2e3238; font-size: 36px; line-height: 1.1; font-weight: bold;"> @yield('title')</h1>
                                </td>
                            </tr>
                            <tr>
                                <td style="border-collapse: collapse; padding-bottom: 25px;">
                                    @if(trim($__env->yieldContent('header_image')))
                                        <img src="@yield('header_image')" style="width: 297px; display: block; margin: 0 auto;">
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td align="center" style="border-collapse: collapse; padding-left: 24px; padding-right: 24px;">
                                    <p style="margin: 0; color: #2e3238; font-family: Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">
                                        @yield('html')
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <td align="center" style="border-collapse: collapse; padding-bottom: 25px;">
                                </td>
                            </tr>
                            <tr>
                                <td align="center" style="border-collapse: collapse;">
                                    <table cellpadding="0" cellspacing="0" border="0" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-size: 0; text-align: center; margin: 0px auto;">
                                        <tr>
                                            <td align="center" style="border-collapse: collapse; padding-bottom: 25px;">
                                                <a href="@yield('link')" target="_blank" style="border-radius: 3px; background: #315efd; padding: 0 15px; display: block; text-align: center; color: #fff; font-size: 14px; font-weight: bold; letter-spacing: 2px; line-height: 46px; text-transform: uppercase; text-decoration: none;">@yield('button_text')</a>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                        <table>
                            <tr>
                                <td align="center" style="{{ $footerStyle }}">
                                    @isset($email)
                                        {!! mail_trans('not_for_you', ['email' => $email, 'unsubscribeLink' => $unsubscribeLink, 'email_preferences_link' => $notificationPreferencesLink]) !!}
                                    @endisset
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>
    </center>
    </body>
</html>
