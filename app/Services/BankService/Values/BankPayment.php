<?php

namespace App\Services\BankService\Values;

class BankPayment
{
    protected string $id;
    protected string $amount;
    protected string $currency;
    protected string $description;
    protected string $date;
    protected ?array $raw = null;

    /**
     * @param string $id
     * @param string $amount
     * @param string $currency
     * @param string $description
     */
    public function __construct(
        string $id,
        string $amount,
        string $currency = "EUR",
        string $description = ""
    ) {
        $this->id = $id;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->description = $description;
    }

    /**
     * @param string $id
     * @param string $amount
     * @param string $currency
     * @param string $description
     * @return static
     */
    public static function make(
        string $id,
        string $amount,
        string $currency = "EUR",
        string $description = ""
    ): static {
        return new static($id, $amount, $currency, $description);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getDate(): string
    {
        return $this->date;
    }

    /**
     * @param string $date
     */
    public function setDate(string $date): static
    {
        $this->date = $date;

        return $this;
    }

    /**
     * @return string
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * @param string $currency
     */
    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param array $raw
     *
     * @noinspection PhpUnused
     */
    public function setRaw(array $raw): static
    {
        $this->raw = $raw;

        return $this;
    }

    /**
     * @noinspection PhpUnused
     */
    public function getRaw(): array|null
    {
        return $this->raw;
    }
}