<x-mail::message>
@lang('mails.task') @lang('mails.priority_changed_to') <span style="color: {{ $task->priority->color }}; font-weight: bold">{{ $task->priority->name }}</span>, @lang('mails.by') {{ $author->name }} :

<x-mail::panel>
# <span style="font-weight: bold">{{ $task->title }}</span>

## @lang('mails.priority')
<span style="color: {{ $oldPriority->color }}; font-weight: bold">{{ $oldPriority->name }}</span> @lang('mails.to') <span style="color: {{ $task->priority->color }}; font-weight: bold">{{ $task->priority->name }}</span>
</x-mail::panel>

@lang('mails.you_can_view_task').

<x-mail::button :url="\App\Filament\Resources\ProjectResource::getUrl('show', ['record' => $task->project, 'task' => $task->id])" color="primary">
@lang('mails.view_task')
</x-mail::button>

</x-mail::message>
