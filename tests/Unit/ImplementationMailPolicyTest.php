<?php

namespace Tests\Unit;

use App\Mail\Auth\UserLoginMail;
use App\Mail\BankConnections\BankConnectionExpiringMail;
use App\Mail\ContactForm\ContactFormMail;
use App\Mail\FeedbackForm\FeedbackFormMail;
use App\Mail\Forus\ForusFundCreatedMail;
use App\Mail\Forus\FundStatisticsMail;
use App\Mail\Forus\IdentityDestroyRequestMail;
use App\Mail\Forus\TransactionVerifyMail;
use App\Mail\Funds\FundBalanceWarningMail;
use App\Mail\Funds\FundRequestClarifications\FundRequestClarificationReceivedMail;
use App\Mail\Funds\FundRequestClarifications\FundRequestClarificationRequestedMail;
use App\Mail\Funds\FundRequests\FundRequestApprovedMail;
use App\Mail\Funds\FundRequests\FundRequestCreatedMail;
use App\Mail\Funds\FundRequests\FundRequestDeniedMail;
use App\Mail\Funds\FundRequests\FundRequestDisregardedMail;
use App\Mail\Funds\FundSponsorCustomNotificationMail;
use App\Mail\Funds\ProviderAppliedMail;
use App\Mail\Funds\ProviderApprovedMail;
use App\Mail\Funds\ProviderInvitationMail;
use App\Mail\Funds\ProviderStateAcceptedMail;
use App\Mail\Funds\ProviderStateRejectedMail;
use App\Mail\Funds\ProviderStateUnsubscribedMail;
use App\Mail\ImplementationMail;
use App\Mail\ProductReservations\ProductReservationAcceptedMail;
use App\Mail\ProductReservations\ProductReservationCanceledMail;
use App\Mail\ProductReservations\ProductReservationProviderMessageMail;
use App\Mail\ProductReservations\ProductReservationRejectedMail;
use App\Mail\Reimbursements\ReimbursementApprovedMail;
use App\Mail\Reimbursements\ReimbursementDeclinedMail;
use App\Mail\Reimbursements\ReimbursementSubmittedMail;
use App\Mail\Share\ShareAppMail;
use App\Mail\User\EmailActivationMail;
use App\Mail\User\EmployeeAddedMail;
use App\Mail\User\FundRequestAssignedBySupervisorMail;
use App\Mail\User\IdentityEmailVerificationMail;
use App\Mail\Vouchers\DeactivationVoucherMail;
use App\Mail\Vouchers\PaymentSuccessBudgetMail;
use App\Mail\Vouchers\ProductBoughtProviderBySponsorMail;
use App\Mail\Vouchers\ProductBoughtProviderMail;
use App\Mail\Vouchers\ProductReservedRequesterMail;
use App\Mail\Vouchers\ProductSoldOutMail;
use App\Mail\Vouchers\RequestPhysicalCardMail;
use App\Mail\Vouchers\SendProductVoucherBySponsorMail;
use App\Mail\Vouchers\SendProductVoucherMail;
use App\Mail\Vouchers\SendVoucherBySponsorMail;
use App\Mail\Vouchers\SendVoucherMail;
use App\Mail\Vouchers\ShareProductVoucherMail;
use App\Mail\Vouchers\VoucherAssignedBudgetMail;
use App\Mail\Vouchers\VoucherAssignedProductMail;
use App\Mail\Vouchers\VoucherExpireSoonBudgetMail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use Tests\TestCase;

class ImplementationMailPolicyTest extends TestCase
{
    private const array CUSTOM_MAILER_POLICY = [
        UserLoginMail::class => false,
        BankConnectionExpiringMail::class => false,
        ContactFormMail::class => false,
        FeedbackFormMail::class => false,
        ForusFundCreatedMail::class => false,
        FundStatisticsMail::class => false,
        IdentityDestroyRequestMail::class => false,
        TransactionVerifyMail::class => false,
        FundBalanceWarningMail::class => false,
        FundRequestClarificationReceivedMail::class => false,
        FundRequestClarificationRequestedMail::class => true,
        FundRequestApprovedMail::class => true,
        FundRequestCreatedMail::class => true,
        FundRequestDeniedMail::class => true,
        FundRequestDisregardedMail::class => true,
        FundSponsorCustomNotificationMail::class => true,
        ProviderAppliedMail::class => false,
        ProviderApprovedMail::class => false,
        ProviderInvitationMail::class => false,
        ProviderStateAcceptedMail::class => false,
        ProviderStateRejectedMail::class => false,
        ProviderStateUnsubscribedMail::class => false,
        ProductReservationAcceptedMail::class => true,
        ProductReservationCanceledMail::class => true,
        ProductReservationProviderMessageMail::class => true,
        ProductReservationRejectedMail::class => true,
        ReimbursementApprovedMail::class => true,
        ReimbursementDeclinedMail::class => true,
        ReimbursementSubmittedMail::class => true,
        ShareAppMail::class => false,
        EmailActivationMail::class => false,
        EmployeeAddedMail::class => false,
        FundRequestAssignedBySupervisorMail::class => false,
        IdentityEmailVerificationMail::class => false,
        DeactivationVoucherMail::class => true,
        PaymentSuccessBudgetMail::class => true,
        ProductBoughtProviderBySponsorMail::class => false,
        ProductBoughtProviderMail::class => false,
        ProductReservedRequesterMail::class => true,
        ProductSoldOutMail::class => false,
        RequestPhysicalCardMail::class => true,
        SendProductVoucherBySponsorMail::class => true,
        SendProductVoucherMail::class => true,
        SendVoucherBySponsorMail::class => true,
        SendVoucherMail::class => true,
        ShareProductVoucherMail::class => true,
        VoucherAssignedBudgetMail::class => true,
        VoucherAssignedProductMail::class => true,
        VoucherExpireSoonBudgetMail::class => true,
    ];

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function testEveryImplementationMailHasExplicitCustomMailerPolicy(): void
    {
        $mailClasses = [];

        foreach (File::allFiles(app_path('Mail')) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $class = 'App\\Mail\\' . str_replace('/', '\\', substr($file->getRelativePathname(), 0, -4));

            if (is_subclass_of($class, ImplementationMail::class) && !(new ReflectionClass($class))->isAbstract()) {
                $mailClasses[] = $class;
            }
        }

        $this->assertEqualsCanonicalizing(
            array_keys(self::CUSTOM_MAILER_POLICY),
            $mailClasses,
            'Update CUSTOM_MAILER_POLICY when a new ImplementationMail subclass is added, removed, or renamed.',
        );
    }

    /**
     * @return void
     */
    public function testCustomMailerAllowlistMatchesPolicy(): void
    {
        $this->assertEqualsCanonicalizing(
            array_keys(array_filter(self::CUSTOM_MAILER_POLICY)),
            Config::get('forus.mail.custom_mailer_allowlist'),
            'The custom mailer allowlist must exactly match the mail classes explicitly permitted by the policy.',
        );
    }
}
