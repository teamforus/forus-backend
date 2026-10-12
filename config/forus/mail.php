<?php

use App\Mail\Funds\FundRequestClarifications\FundRequestClarificationRequestedMail;
use App\Mail\Funds\FundRequests\FundRequestApprovedMail;
use App\Mail\Funds\FundRequests\FundRequestCreatedMail;
use App\Mail\Funds\FundRequests\FundRequestDeniedMail;
use App\Mail\Funds\FundRequests\FundRequestDisregardedMail;
use App\Mail\Funds\FundSponsorCustomNotificationMail;
use App\Mail\ProductReservations\ProductReservationAcceptedMail;
use App\Mail\ProductReservations\ProductReservationCanceledMail;
use App\Mail\ProductReservations\ProductReservationProviderMessageMail;
use App\Mail\ProductReservations\ProductReservationRejectedMail;
use App\Mail\Reimbursements\ReimbursementApprovedMail;
use App\Mail\Reimbursements\ReimbursementDeclinedMail;
use App\Mail\Reimbursements\ReimbursementSubmittedMail;
use App\Mail\Vouchers\DeactivationVoucherMail;
use App\Mail\Vouchers\PaymentSuccessBudgetMail;
use App\Mail\Vouchers\ProductReservedRequesterMail;
use App\Mail\Vouchers\RequestPhysicalCardMail;
use App\Mail\Vouchers\SendProductVoucherBySponsorMail;
use App\Mail\Vouchers\SendProductVoucherMail;
use App\Mail\Vouchers\SendVoucherBySponsorMail;
use App\Mail\Vouchers\SendVoucherMail;
use App\Mail\Vouchers\ShareProductVoucherMail;
use App\Mail\Vouchers\VoucherAssignedBudgetMail;
use App\Mail\Vouchers\VoucherAssignedProductMail;
use App\Mail\Vouchers\VoucherExpireSoonBudgetMail;
use Illuminate\Support\Env;

return [
    'custom_mailer_allowlist' => [
        FundRequestCreatedMail::class,
        FundRequestApprovedMail::class,
        FundRequestDeniedMail::class,
        FundRequestDisregardedMail::class,
        FundRequestClarificationRequestedMail::class,
        FundSponsorCustomNotificationMail::class,
        ReimbursementSubmittedMail::class,
        ReimbursementApprovedMail::class,
        ReimbursementDeclinedMail::class,
        ProductReservationAcceptedMail::class,
        ProductReservationCanceledMail::class,
        ProductReservationRejectedMail::class,
        ProductReservationProviderMessageMail::class,
        ProductReservedRequesterMail::class,
        ShareProductVoucherMail::class,
        VoucherAssignedBudgetMail::class,
        VoucherAssignedProductMail::class,
        DeactivationVoucherMail::class,
        VoucherExpireSoonBudgetMail::class,
        RequestPhysicalCardMail::class,
        SendVoucherMail::class,
        SendProductVoucherMail::class,
        SendVoucherBySponsorMail::class,
        SendProductVoucherBySponsorMail::class,
        PaymentSuccessBudgetMail::class,
    ],

    'log_production' => Env::get('MAIL_LOG_PRODUCTION', false),
    'log_attachments' => Env::get('MAIL_LOG_ATTACHMENTS', false),

    'log_storage_driver' => env('MAIL_LOG_STORAGE_DRIVER', 'local'),
    'log_storage_path' => env('MAIL_LOG_STORAGE_PATH', 'attachments'),

    'from' => [
        'no-reply' => env('MAIL_FROM_ADDRESS', 'no-reply@forus.io'),
        'name' => env('MAIL_FROM_NAME', 'Stichting Forus'),
    ],

    'email-preferences-link' => env('EMAIL_PREFERENCES_LINK'),
    'email-not-you-link' => env('EMAIL_NOT_YOU_LINK'),
    'max_identity_emails' => env('MAX_IDENTITY_EMAILS', 4),
];
