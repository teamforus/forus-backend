<?php

namespace App\Mail\Forus;

use App\Mail\ImplementationMail;
use App\Services\Forus\Notification\EmailFrom;
use Illuminate\Mail\Mailable;

/**
 * Class FundCreatedMail
 * @package App\Mail\Funds\Forus
 */
class ForusFundCreatedMail extends ImplementationMail
{
    private $fundName;
    private $organizationName;

    public function __construct(
        string $fundName,
        string $organizationName,
        ?EmailFrom $emailFrom
    ) {
        $this->setMailFrom($emailFrom);

        $this->fundName = $fundName;
        $this->organizationName = $organizationName;
    }

    public function build(): Mailable
    {
        return $this->buildBase()
            ->subject(mail_trans('forus/fund_created.title', [
                'fund_name' => $this->fundName
            ]))
            ->view('emails.forus.new_fund_created', [
                'fund_name' => $this->fundName,
                'organization_name' => $this->organizationName
            ]);
    }
}
