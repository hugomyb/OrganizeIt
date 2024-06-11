<?php

namespace App\Jobs;

use App\Helpers\MailHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $emailClass;
    protected $recipient;
    protected $params;

    public function __construct($emailClass, $recipient, ...$params)
    {
        $this->emailClass = $emailClass;
        $this->recipient = $recipient;
        $this->params = $params;
    }

    public function handle()
    {
        MailHelper::sendEmail($this->emailClass, $this->recipient, ...$this->params);
    }
}
