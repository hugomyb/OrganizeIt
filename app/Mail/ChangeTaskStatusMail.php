<?php

namespace App\Mail;

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

    /**
     * Create a new message instance.
     */
    public function __construct($task, $author, $oldStatus)
    {
        $this->task = $task;
        $this->author = $author;
        $this->oldStatus = $oldStatus;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: env('MAIL_FROM_ADDRESS'),
            subject: '[' . $this->task->project->name . '] ' . __('mails.task') . ' ' . Str::limit($this->task->title, 20) . ' ' . __('mails.status_changed_to') . ' ' . $this->task->status->name . ' ' . __('mails.by') . ' ' . $this->author->name,
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
