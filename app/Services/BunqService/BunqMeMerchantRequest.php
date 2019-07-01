<?php


namespace App\Services\BunqService;


class BunqMeMerchantRequest
{
    private $uuid;
    private $status;
    private $bunqme_uuid;
    private $bunqme_type;
    private $issuer_authentication_url;

    public function __construct(
        string $uuid,
        string $status,
        string $bunqme_uuid,
        string $bunqme_type,
        string $issuer_authentication_url = null
    ) {
        $this->uuid = $uuid;
        $this->status = $status;
        $this->bunqme_uuid = $bunqme_uuid;
        $this->bunqme_type = $bunqme_type;
        $this->issuer_authentication_url = $issuer_authentication_url;
    }

    public static function makeFromObject($object) {
        return new self(
            $object->uuid,
            $object->status,
            $object->bunqme_uuid,
            $object->bunqme_type,
            $object->issuer_authentication_url
        );
    }

    /**
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getBunqmeUuid(): string
    {
        return $this->bunqme_uuid;
    }

    /**
     * @return string
     */
    public function getBunqmeType(): string
    {
        return $this->bunqme_type;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->issuer_authentication_url;
    }

}