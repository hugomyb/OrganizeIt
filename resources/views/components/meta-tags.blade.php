@php
    use App\Models\Project;
    use App\Models\Task;
    use Illuminate\Support\Facades\Route;

    $project = Project::find(Route::current()->parameters()['record']);
    $task = request()->query('task') !== null ? Task::find(request()->query('task')) : null;
@endphp

@if($task)
    <meta property="og:title" content="{{ $project->name }}">
    <meta property="og:description" content="{{ $task->title }}">
    <meta property="og:image" content="{{ asset('/img/organize-it.png') }}">
    <meta property="og:url" content="{{ url()->full() }}">
    <meta property="og:type" content="website">
@else
    <meta property="og:title" content="{{ $project->name }}">
    <meta property="og:description" content="{{ __('project.project') . ': ' . $project->name }}">
    <meta property="og:image" content="{{ asset('/img/organize-it.png') }}">
    <meta property="og:url" content="{{ url()->full() }}">
    <meta property="og:type" content="website">
@endif
