<?php

namespace App\Helpers;

use League\CommonMark\Exception\CommonMarkException;

class Markdown
{
    /**
     * @param string $content
     * @throws CommonMarkException
     * @return string
     */
    public static function convert(string $content): string
    {
        return resolve('markdown.converter')->convert($content)->getContent();
    }
}
