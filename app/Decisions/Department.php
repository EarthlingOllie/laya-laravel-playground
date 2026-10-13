<?php

namespace App\Decisions;

use MarcReichel\Laya\Attributes\Describe;

enum Department: string
{
    #[Describe('invoices, payments, refunds')]
    case Billing = 'billing';

    #[Describe('bugs, outages, system errors')]
    case Technical = 'technical';

    #[Describe('login, passwords, account settings')]
    case Account = 'account';

    #[Describe('everything else')]
    case Other = 'other';
}
