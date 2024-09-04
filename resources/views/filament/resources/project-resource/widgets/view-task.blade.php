<div
    x-init="init"
    x-data="{
        init() {
            if (!window.location.search.includes('task={{ $task->id }}')) {
                setTimeout(() => {
                    const url = new URL(window.location);
                    url.searchParams.set('task', {{ $task->id }});
                    window.history.pushState({}, '', url);
                }, 500);
            }
        }
    }">
    <livewire:modal-content :task="$task" />
</div>
