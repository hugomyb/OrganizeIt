<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class NewCommentMail extends Mailable
{
    use Queueable, SerializesModels;

    public $task;
    public $comment;
    public $author;

    /**
     * Create a new message instance.
     */
    public function __construct($task, $comment)
    {
        $this->task = $task;
        $this->comment = $comment;
        $this->author = $comment->user->name;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: env('MAIL_FROM_ADDRESS'),
            subject: '[' . $this->task->project->name . '] ' . __('mails.new_comment', ['author' => $this->author, 'task' => Str::limit($this->task->title, 25)]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.new-comment',
            with: [
                'task' => $this->task,
                'comment' => $this->comment,
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
