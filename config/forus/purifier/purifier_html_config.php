<?php

return [
    // Defines the HTML doctype to be used
    'HTML.Doctype' => 'HTML 4.01 Transitional',

    // Specifies the allowed HTML elements and their attributes
    'HTML.Allowed' => implode(',', [
        'div[class|style]',
        'h1[style]',
        'h2[style]',
        'h3[style]',
        'h4[style]',
        'h5[style]',
        'h6[style]',
        'b[style]',
        'strong[style]',
        'i[style]',
        'em[style]',
        'u[style]',
        'a[href|title|style]',
        'ul[style]',
        'ol[style]',
        'li[style]',
        'p[style]',
        'br',
        'table',
        'tr',
        'td',
        'th',
        'thead',
        'tbody',
        'span[style]',
        'img[width|height|alt|src]',
        'iframe[src|width|height|frameborder|allowfullscreen]',
    ]),

    // Defines the allowed CSS properties
    'CSS.AllowedProperties' => implode(',', [
        'font',
        'font-size',
        'font-weight',
        'font-style',
        'font-family',
        'text-decoration',
        'padding',
        'color',
        'background',
        'background-color',
        'text-align',
        'width',
        'margin',
        'display',
    ]),

    // Enables support for tricky CSS properties
    'CSS.AllowTricky' => true,

    // Disables automatic paragraph formatting
    'AutoFormat.AutoParagraph' => false,

    // Prevents the removal of empty HTML elements
    'AutoFormat.RemoveEmpty' => false,

    // Enables safe iframes for embedding content
    'HTML.SafeIframe' => true,

    // Defines the regular expression for safe iframe sources
    'URI.SafeIframeRegexp' => implode('', [
        '%^(https?:)?//(',
        'www\.youtube\.com/embed/|',
        'www\.youtube-nocookie\.com/embed/|',
        'player\.vimeo\.com/video/',
        ')%',
    ]),

    // Defines allowed URI schemes
    'URI.AllowedSchemes' => [
        'http' => true,
        'https' => true,
    ],
];
