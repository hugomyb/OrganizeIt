<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AssignToTaskMail extends Mailable
{
    use Queueable, SerializesModels;

    public $task;
    public $author;

    /**
     * Create a new message instance.
     */
    public function __construct($task, $author)
    {
        $this->task = $task;
        $this->author = $author;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: env('MAIL_FROM_ADDRESS'),
            subject: '[' . $this->task->project->name . '] ' . __('mails.assign_to_task'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.assign-to-task',
            with: [
                'task' => $this->task,
                'author' => $this->author
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
