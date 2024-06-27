<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class ChangeTaskStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public $task;
    public $author;
    public $oldStatus;
    public $recipient;

    /**
     * Create a new message instance.
     */
    public function __construct($task, $author, $oldStatus, User $recipient)
    {
        $this->task = $task;
        $this->author = $author;
        $this->oldStatus = $oldStatus;
        $this->recipient = $recipient;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = '';

        if ($this->recipient->hasRole('Client')) {
            $subject = '[' . $this->task->project->name . '] ' . __('mails.task') . ' ' . Str::limit($this->task->title, 20) . ' ' . __('mails.status_changed_to') . ' ' . $this->task->status->name;
        } else {
            $subject = '[' . $this->task->project->name . '] ' . __('mails.task') . ' ' . Str::limit($this->task->title, 20) . ' ' . __('mails.status_changed_to') . ' ' . $this->task->status->name . ' ' . __('mails.by') . ' ' . $this->author->name;
        }

        return new Envelope(
            from: env('MAIL_FROM_ADDRESS'),
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.change-task-status',
            with: [
                'task' => $this->task,
                'author' => $this->author,
                'oldStatus' => $this->oldStatus,
                'recipient' => $this->recipient,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
