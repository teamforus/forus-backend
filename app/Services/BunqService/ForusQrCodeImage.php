<?php


namespace App\Services\BunqService;


class ForusQrCodeImage
{
    private $token;
    private $base64;
    private $content_type;

    public function __construct(
        string $token,
        string $base64,
        string $content_type
    ) {
        $this->token = $token;
        $this->base64 = $base64;
        $this->content_type = $content_type;
    }

    public static function makeFromObject($object) {
        return new self(
            $object->token,
            $object->base64,
            $object->content_type
        );
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @return string
     */
    public function getBase64(): string
    {
        return $this->base64;
    }

    /**
     * @return string
     */
    public function getContentType(): string
    {
        return $this->content_type;
    }


}