<div class="flex flex-col">
    @include('infolists.components.breadcrumb-entry', ['record' => $task])

    <div
        x-data="{
            setCaretAtStartEnd() {
                const sel = document.getSelection();

                // set caret at end on contenteditable element
                const range = document.createRange();
                range.selectNodeContents(document.getElementById('titleEditable'));
                range.collapse(false);
                sel.removeAllRanges();
                sel.addRange(range);
            }
        }"
        class="flex items-center gap-3 mt-6 border-b border-gray-100 dark:border-gray-700 pb-6">
        @if($task->creator)
            <img src="{{ asset($task->creator->avatar) }}" alt="{{ $task->creator->name }}"
                 title="{{ $task->creator->name }}"
                 class="rounded-full" style="height: 60px">
        @else
            <img class="rounded-full" src="{{ asset('img/avatar.png') }}" alt="avatar" style="height: 60px">
        @endif

        <span class="text-xl font-semibold relative inline-block task-title"
              id="titleEditable"
              x-init="initContentEditable"
              x-data="{
                initContentEditable() {
                    document.getElementById('titleEditable').addEventListener('keypress', (evt) => {
                        if (evt.which === 13) {
                            evt.preventDefault();
                        }
                    });
                },
              }"
              x-ref="taskTitle"
              contenteditable="false"
              x-on:blur="$wire.saveTaskTitle({{ $task->id }}, $event.target.innerText)"
              @keyup.enter.prevent="$wire.saveTaskTitle({{ $task->id }}, $event.target.innerText)">
            {{ $task->title }}

            <x-phosphor-pencil class="h-5 w-5 absolute inline edit-title cursor-pointer"
                               @click="$refs.taskTitle.attributes.contenteditable.value = 'true'; $refs.taskTitle.focus(); setCaretAtStartEnd();"
                               style="margin-left: 10px; margin-top: 5px; color: #8a8a8a"/>
        </span>
    </div>
</div>
