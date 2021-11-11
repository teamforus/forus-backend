<?php

namespace App\Mail\Models;

use Eduardokum\LaravelMailAutoEmbed\Models\EmbeddableEntity;

/**
 * @noinspection PhpUnused
 */
class EmbedQrCode implements EmbeddableEntity
{
    protected $value = '';

    public function __construct($value)
    {
        $this->value = $value;
    }

    public static function findEmbeddable($id)
    {
        return new static($id);
    }

    public function getRawContent()
    {
        [$type, $content] = explode('-', $this->value);

        return make_qr_code($type, $content, 300);
    }

    public function getFileName(): string
    {
        return 'qr_code.png';
    }

    public function getMimeType()
    {
        return null;
    }
}