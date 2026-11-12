<?php

namespace App\Modules\DCP\Data;

use Carbon\CarbonImmutable;
use JsonException;

readonly class GelrepasCardData
{
    public const string ITEM = 'card';
    public const string QR_TYPE = 'pass';
    public const string TAG_KEY = 'tag_001';

    public string $qrCode;

    /**
     * @param string $printLabel
     * @param string $firstName
     * @param string $lastName
     * @param CarbonImmutable $birthDate
     * @param string $cityCode
     * @param string $passCode
     * @param string|null $qrCode
     * @param string $tagKey
     * @throws JsonException
     */
    public function __construct(
        public string $printLabel,
        public string $firstName,
        public string $lastName,
        public CarbonImmutable $birthDate,
        public string $cityCode,
        public string $passCode,
        ?string $qrCode = null,
        public string $tagKey = self::TAG_KEY,
    ) {
        $this->qrCode = $qrCode ?? json_encode([
            'type' => self::QR_TYPE,
            'value' => $this->passCode,
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->tagKey,
            'printedCardType' => $this->printLabel,
            'printedFirstName' => $this->firstName,
            'printedLastName' => $this->lastName,
            'printedDateOfBirth' => $this->birthDate->format('d-m-Y'),
            'printedCityCode' => $this->cityCode,
            'printedQrCode' => $this->qrCode,
            'printedCardNumber' => $this->passCode,
        ];
    }
}
