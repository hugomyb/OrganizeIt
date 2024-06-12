<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class NewCommitMail extends Mailable
{
    use Queueable, SerializesModels;

    public $task;
    public $author;
    public $commit;

    /**
     * Create a new message instance.
     */
    public function __construct($task, $author, $commit)
    {
        $this->task = $task;
        $this->author = $author;
        $this->commit = $commit;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: env('MAIL_FROM_ADDRESS'),
            subject: '[' . $this->task->project->name . '] ' . __('mails.new_commit', ['author' => $this->author->name, 'task' => Str::limit($this->task->title, 25), 'commit' => $this->commit]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.new-commit',
            with: [
                'task' => $this->task,
                'author' => $this->author,
                'commit' => $this->commit,
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
