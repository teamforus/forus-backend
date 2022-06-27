<?php

namespace App\Services\BNGService\Responses\Entries;

use App\Services\BNGService\Data\PaymentInfoData;
use App\Services\BNGService\Responses\Entries\Payment as PaymentBNG;
use SimpleXMLElement;

class BulkPayment
{
    protected $requestedExecutionDate;
    protected $paymentInitiator;
    protected $redirectToken;
    protected $bulkPaymentId;
    protected $messageId;
    protected $payments;
    protected $debtor;

    /**
     * @param PaymentInitiator $paymentInitiator
     * @param Account $debtor
     * @param PaymentBNG[] $payments
     * @param PaymentInfoData $paymentInfo
     * @param string|null $messageId
     */
    public function __construct(
        PaymentInitiator $paymentInitiator,
        Account $debtor,
        array $payments,
        PaymentInfoData $paymentInfo,
        string $messageId = null
    ) {
        $this->requestedExecutionDate = $paymentInfo->getExecutionDate();
        $this->paymentInitiator = $paymentInitiator;
        $this->bulkPaymentId = $paymentInfo->getPaymentId();
        $this->redirectToken = $paymentInfo->getRedirectToken();
        $this->messageId = $messageId;
        $this->payments = $payments;
        $this->debtor = $debtor;
    }

    /**
     * @return string
     */
    public function getRedirectToken(): string
    {
        return $this->redirectToken;
    }

    /**
     * @return PaymentInitiator
     * @noinspection PhpUnused
     */
    public function getPaymentInitiator(): PaymentInitiator
    {
        return $this->paymentInitiator;
    }

    /**
     * @return array
     * @noinspection PhpUnused
     */
    public function getPayments(): array
    {
        return $this->payments;
    }

    /**
     * @return string|null
     */
    public function getBulkPaymentId(): ?string
    {
        return $this->bulkPaymentId;
    }

    /**
     * @return string|null
     */
    public function getMessageId(): ?string
    {
        return $this->messageId;
    }

    /**
     * @return string
     */
    public function getPaymentsAmountSum(): string
    {
        return number_format(array_sum(array_map(function(Payment $payment) {
            return floatval($payment->getAmount()->getAmount());
        }, $this->payments)), 2, '.', '');
    }

    /**
     * @return string|null
     */
    public function getRequestedExecutionDate(): ?string
    {
        return $this->requestedExecutionDate;
    }

    /**
     * @return string
     */
    public function toXml(): string
    {
        $docAttributes = implode(' ', [
            'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"',
            'xmlns:xsd="http://www.w3.org/2001/XMLSchema"',
            'xmlns="urn:iso:std:iso:20022:tech:xsd:pain.001.001.03"',
            'xsi:schemaLocation="urn:iso:std:iso:20022:tech:xsd:pain.001.001.03"',
        ]);

        $document = new SimpleXMLElement("<Document $docAttributes />");
        $customerCreditTransferInitiation = $document->addChild('CstmrCdtTrfInitn');

        $groupHeader = $customerCreditTransferInitiation->addChild('GrpHdr');
        $groupHeader->addChild('MsgId', $this->getMessageId());
        $groupHeader->addChild('CreDtTm', date('Y-m-d\TH:i:s'));
        $groupHeader->addChild('NbOfTxs', count($this->payments));
        $groupHeader->addChild('CtrlSum', $this->getPaymentsAmountSum());
        $groupHeader->addChild('InitgPty')->addChild('Nm', htmlspecialchars($this->paymentInitiator->getName()));


        $paymentInformation = $customerCreditTransferInitiation->addChild('PmtInf');
        $paymentInformation->addChild('PmtInfId', $this->getBulkPaymentId());
        $paymentInformation->addChild('PmtMtd', 'TRF');
        $paymentInformation->addChild('BtchBookg', 'true');
        $paymentInformation->addChild('NbOfTxs', count($this->payments));
        $paymentInformation->addChild('CtrlSum', $this->getPaymentsAmountSum());
        $paymentInformation->addChild('PmtTpInf')->addChild('SvcLvl')->addChild('Cd', 'SEPA');
        $paymentInformation->addChild('ReqdExctnDt', $this->getRequestedExecutionDate());

        $paymentInformation->addChild('Dbtr')->addChild('Nm', htmlspecialchars($this->debtor->getName()));
        $paymentInformation->addChild('DbtrAcct')->addChild('Id')->addChild('IBAN', $this->debtor->getIban());
        $paymentInformation->addChild('DbtrAgt')->addChild('FinInstnId');

        foreach ($this->payments as $payment) {
            $amount = $payment->getAmount();

            $creditTransferTransactionInformation = $paymentInformation->addChild('CdtTrfTxInf');
            $creditTransferTransactionInformation->addChild('PmtId')->addChild('EndToEndId', $payment->getPaymentId());
            $creditTransferTransactionInformation->addChild('Amt')->addChild('InstdAmt', $amount->getAmount())->addAttribute('Ccy', $amount->getCurrency());
            $creditTransferTransactionInformation->addChild('Cdtr')->addChild('Nm', htmlspecialchars($payment->getCreditor()->getName()));
            $creditTransferTransactionInformation->addChild('CdtrAcct')->addChild('Id')->addChild('IBAN', $payment->getCreditor()->getIban());
            $creditTransferTransactionInformation->addChild('RmtInf')->addChild('Ustrd', htmlspecialchars($payment->getDescription()));
        }

        return $document->asXML();
    }
}