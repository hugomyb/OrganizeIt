<x-mail::message>
{{ __('mails.has_added_comment', ['author' => $comment->user->name]) }}:

<x-mail::panel>
# <span style="font-weight: bold">{{ $task->title }}</span>

## {{ __('mails.comment') }}
<div
style="display: grid; grid-template-columns: auto 1fr; grid-template-rows: auto auto; gap: 10px; background-color: #f9f9f9; padding: 10px; border-radius: 10px; margin-top: 10px;">
<div style="grid-column: 1 / 2; grid-row: 1 / 2;">
<img src="{{ $comment->user->avatar_url }}" alt="{{ $comment->user->name }}"
style="border-radius: 50%; width: 40px; height: 40px; margin-right: 10px;">
<p style="margin: 0; font-weight: bold;">{{ $comment->user->name }}</p>
</div>
<div style="grid-column: 1 / 3; grid-row: 2 / 3; padding: 10px;">
<p style="margin: 0;">{{ $comment->content }}</p>
</div>
</div>
</x-mail::panel>

{{ __('mails.you_can_view_comment') }}.

<x-mail::button
:url="\App\Filament\Resources\ProjectResource::getUrl('show', ['record' => $task->project, 'task' => $task->id])"
color="primary">
{{ __('mails.view_comment') }}
</x-mail::button>

</x-mail::message>
