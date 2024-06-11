<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Mail;

class MailHelper
{
    public static function sendEmail($emailClass, $recipient, ...$params): void
    {
        Mail::to($recipient)->send(new $emailClass(...$params));
    }
}
