<?php

namespace App\Jobs;

use App\Helpers\MailHelper;
use App\Models\Setting;
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
        $settings = Setting::where('user_id', $this->recipient->id)
            ->where('key', 'notifications')
            ->first();

        if ($settings) {
            $notificationSettings = json_decode($settings->value, true) ?? [];
            $notificationKey = class_basename($this->emailClass);

            if (empty($notificationSettings[$notificationKey]['enabled'])) {
                return;
            }

            if ($notificationKey === 'ChangeTaskStatusMail' && isset($this->params[0]['status_id'])) {
                $statusId = $this->params[0]['status_id'];
                if (!in_array($statusId, $notificationSettings[$notificationKey]['statuses'] ?? [])) {
                    return;
                }
            }
        }

        MailHelper::sendEmail($this->emailClass, $this->recipient, ...$this->params);
    }
}
