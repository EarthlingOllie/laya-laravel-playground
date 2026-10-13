<?php

namespace App\Decisions;

use MarcReichel\Laya\Attributes\Describe;
use MarcReichel\Laya\Attributes\Scale;

#[Scale]
enum Urgency: int
{
    #[Describe('can wait a week or more')]
    case NotUrgent = 0;

    #[Describe('should be handled in the next day or two')]
    case Soon = 1;

    #[Describe('blocks the customer right now')]
    case Blocking = 2;
}
