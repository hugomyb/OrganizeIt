<x-mail::message>
{{ __('mails.new_commit_added', ['author' => $author->name]) }}:

<x-mail::panel>
# <span style="font-weight: bold">Commit : {{ $commit }}</span>
## <span style="font-weight: bold">{{ $task->title }}</span>
</x-mail::panel>

{{ __('mails.you_can_view_task') }}.

<x-mail::button
:url="\App\Filament\Resources\ProjectResource::getUrl('show', ['record' => $task->project, 'task' => $task->id])"
color="primary">
{{ __('mails.view_task') }}
</x-mail::button>

</x-mail::message>
