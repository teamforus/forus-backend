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
        'span[style]',
        'img[data-auto-embed|width|height|alt|src]',
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

    // Defines allowed URI schemes
    'URI.AllowedSchemes' => [
        'http' => true,
        'https' => true,
        'data' => true,
    ],
];
