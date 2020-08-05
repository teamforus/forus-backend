<?php

namespace App\Mail\Funds\FundRequestRecords;

use App\Mail\ImplementationMail;
use App\Services\Forus\Notification\EmailFrom;
use Illuminate\Mail\Mailable;

/**
 * Class FundRequestCreatedMail
 * @package App\Mail\FundRequests
 */
class FundRequestRecordDeclinedMail extends ImplementationMail
{
    private $rejectionNote;
    private $fundName;
    private $link;

    /**
     * FundRequestRecordDeclinedMail constructor.
     * @param string $fundName
     * @param string|null $rejectionNote
     * @param string $link
     * @param EmailFrom|null $emailFrom
     */
    public function __construct(
        string $fundName,
        ?string $rejectionNote,
        string $link,
        ?EmailFrom $emailFrom
    ) {
        $this->setMailFrom($emailFrom);
        $this->rejectionNote = $rejectionNote;
        $this->fundName = $fundName;
        $this->link = $link;
    }

    public function build(): Mailable
    {
        return $this->buildBase()
            ->subject(mail_trans('fund_request_record_declined.title', ['fund_name' => $this->fundName]))
            ->view('emails.funds.fund-request-records.fund_request-declined', [
                'fund_name' => $this->fundName,
                'webshop_link' => $this->link,
                'rejection_note' => $this->rejectionNote,
            ]);
    }
}
