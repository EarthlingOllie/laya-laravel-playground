<?php

namespace App\Decisions;

use MarcReichel\Laya\Attributes\Describe;

enum Topic: string
{
    #[Describe('invoices, payments, refunds')]
    case Billing = 'billing';

    #[Describe('login, passwords, 2FA')]
    case Account = 'account';

    #[Describe('deliveries, tracking, returns')]
    case Shipping = 'shipping';

    #[Describe('a bug or something not working')]
    case Bug = 'bug';
}
