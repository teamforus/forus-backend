<?php
    // styles
    $baseFont = "Verdana, Arial, sans-serif;";

    $textLeft = "text-align: left;";
    $textCenter = "text-align: center;";
    $textRight = "text-align: right;";

    $wrapperStyle = "padding: 30px 0; background-color: #F6F5F5; position: relative; background-repeat: no-repeat; background-position: 100% 0;";
    $emailInnerStyle = "position: relative; z-index: 1; width: 650px; margin: auto; max-width: 100%;";
    $emailBodyStyle  = "padding: 35px 35px 35px; border-bottom: 3px solid #dfe4ec; position: relative; border: 1px solid #efefef; background-color: #fff; border-radius: 8px;";
    $emailFooterStyle = "color: #9397A3; font: 400 12px/20px $baseFont $textCenter padding: 10px 5px 10px; cursor: default;";
    $emailFooterLinkStyle = "color: #315EFD; text-decoration: underline; font: inherit;";

    $h1Style = "font: 700 32px/38px $baseFont color: #1e1e1e; margin: 0 0 25px; $textLeft; cursor: default;";
    $h2Style = "font: 700 25px/32px $baseFont color: #1e1e1e; margin: 0 0 20px; $textLeft; cursor: default;";
    $h3Style = "font: 700 21px/28px $baseFont color: #1e1e1e; margin: 0 0 15px; $textLeft; cursor: default;";
    $h4Style = "font: 700 18px/24px $baseFont color: #1e1e1e; margin: 0 0 10px; $textLeft; cursor: default;";
    $h5Style = "font: 700 16px/20px $baseFont color: #1e1e1e; margin: 0 0 10px; $textLeft; cursor: default;";

    $textStyle = "margin: 0 0 15px; font: 400 15px/22px $baseFont color: #383D45; $textLeft; cursor: default;";
    $textMutedStyle = $textStyle . "font-size: 14px; line-height: 22px; color: #646f79; cursor: default;";

    $linkBlockStyle = "margin: 0 0 15px;";
    $linkStyle = "font: 400 16px/28px $baseFont color: #383D45; cursor: pointer; color: #315EFD; text-decoration: underline;";

    $btnStyle = "display: inline-block; padding: 5px 75px; font: 600 14px/40px $baseFont color: #fff; border-radius: 3px; text-decoration: none;";
    $btnDangerStyle = $btnStyle . "background-color: #bc2527;";
    $btnSuccessStyle = $btnStyle . "background-color: #74c86b;";
    $btnPrimaryStyle = $btnStyle . "background-color: #315EFD;";

    $sectionStyle = "margin: 0 0 10px;";
    $ellipsisStyle = "display: block; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;";
    $marginLessStyle = 'margin-bottom: 0;';
    $separatorStyle = "width: 100%; margin: 0 0 25px; height: 1px; border-bottom: 1px solid #dfe4ec; padding-top: 15px;";

    $markdownStyles = '';

    return [
        'h1' => $h1Style,
        'h2' => $h2Style,
        'h3' => $h3Style,
        'h4' => $h4Style,
        'h5' => $h5Style,
        'text' => $textStyle,
        'link' => $linkStyle,
        'link_block' => $linkBlockStyle,
        'text_left' => $textLeft,
        'text_center' => $textCenter,
        'text_right' => $textRight,
        'margin_less' => $marginLessStyle,
        'space' => $sectionStyle,
        'separator' => $separatorStyle,
        'button_primary' => $btnPrimaryStyle,
        'button_success' => $btnSuccessStyle,
        'button_danger' => $btnDangerStyle,

        'markdown' => $markdownStyles,

        'body' => $emailBodyStyle,
        'inner' => $emailInnerStyle,
        'wrapper' => $wrapperStyle,
        'email_footer' => $emailFooterStyle,
    ];