<x-mail::message>
{{ __('mails.new_task_added', ['author' => $author->name]) }}:

<x-mail::panel>
<span style="font-weight: bold">{{ $task->group->name }}</span>
# <span style="font-weight: bold">{{ $task->title }}</span>

<x-mail::table>
| {{ __('mails.priority') }}  | {{ __('mails.status') }}   |
| :-------------------------: |:--------------------------:|
| <span style="color: {{ $task->priority->color }}; font-weight: bold">{{ $task->priority->name }}</span> | <span style="color: {{ $task->status->color }}; font-weight: bold">{{ $task->status->name }}</span> |
</x-mail::table>
</x-mail::panel>

{{ __('mails.you_can_view_task') }}.

<x-mail::button
:url="\App\Filament\Resources\ProjectResource::getUrl('show', ['record' => $task->project, 'task' => $task->id])"
color="primary">
{{ __('mails.view_task') }}
</x-mail::button>

</x-mail::message>
