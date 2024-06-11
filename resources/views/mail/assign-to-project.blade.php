<x-mail::message>
# {{ $author->name . ' ' . __('mails.added_you_to')}} **{{ $project->name }}**

{{ __('mails.you_can_view_project') }}.

<x-mail::button :url="\App\Filament\Resources\ProjectResource::getUrl('show', ['record' => $project])" color="primary">
{{ __('mails.view_project') }}
</x-mail::button>

</x-mail::message>
