<?php

namespace App\Concerns;

use Filament\Notifications\Notification;

trait CanShowNotification {
    public function showNotification($title, $type = 'success', $body = null, $duration = 2000): void
    {
        Notification::make()
            ->{$type}()
            ->title($title)
            ->duration(2000)
            ->send();
    }
}
