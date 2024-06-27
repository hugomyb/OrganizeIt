<x-mail::message>
{{ __('mails.task') . ' ' . __('mails.status_changed_to') . ' ' }} <span style="color: {{ $task->status->color }}; font-weight: bold">{{ $task->status->name }}</span>{{ $recipient->hasRole('Client') ? '' : ', ' . __('mails.by') . ' ' . $author->name }} :

<x-mail::panel>
<span style="font-weight: bold">{{ $task->group->name }}</span>
# <span style="font-weight: bold">{{ $task->title }}</span>

## {{ __('mails.status') }}
<span style="color: {{ $oldStatus->color }}; font-weight: bold">{{ $oldStatus->name }}</span> {{ __('mails.to') }} <span style="color: {{ $task->status->color }}; font-weight: bold">{{ $task->status->name }}</span>
</x-mail::panel>

{{ __('mails.you_can_view_task') }}.

<x-mail::button :url="\App\Filament\Resources\ProjectResource::getUrl('show', ['record' => $task->project, 'task' => $task->id])" color="primary">
{{ __('mails.view_task') }}
</x-mail::button>

</x-mail::message>
