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
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
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
     * @return BankPayment
     */
    public function setDate(string $date): self
    {
        $this->date = $date;

        return $this;
    }

    /**
     * @param string $amount
     * @return BankPayment
     */
    public function setAmount(string $amount): self
    {
        $this->amount = $amount;

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
     * @return BankPayment
     */
    public function setCurrency(string $currency): self
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
     * @return BankPayment
     */
    public function setDescription(string $description): self
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
     * @return BankPayment
     * @noinspection PhpUnused
     */
    public function setRaw(array $raw): self
    {
        $this->raw = $raw;

        return $this;
    }

    /**
     * @return array
     * @noinspection PhpUnused
     */
    public function getRaw(): array
    {
        return $this->raw;
    }
}