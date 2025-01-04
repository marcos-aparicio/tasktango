{{-- must be use within the task-modal component --}}
@isset($task)
<div class="collapse collapse-arrow overflow-visible" wire:key="subtasks-from-task-{{$task->id}}">
@if($task->subTasks()->valid()->count() > 0)
        <input type="checkbox" checked/>
        <div class="collapse-title flex gap-2 items-center justify-center">
            <p class="font-bold text-lg">Subtasks</p>
            <p>{{$task->subTasks()->completed()->count()}}/{{$task->subTasks()->valid()->count()}}</p>
        </div>
        <div class="flex flex-col gap-2 collapse-content">
        @foreach($task->subTasks()->pending()->get() as $subTask)
            <livewire:task.card :task="$subTask" wire:key="task-{{$task->id}}-subtask-{{ $subTask->id }}-pending" class="md:w-full"/>
        @endforeach
        @if($task->subTasks()->completed()->count() > 0)
            <x-collapse dusk="completed-tasks">
                <x-slot:heading class="!text-sm flex justify-end gap-4">
                    <span dusk="title">
                        Completed Subtasks
                    </span>
                </x-slot:heading>
                <x-slot:content class="flex flex-col gap-2 items-end">
                    @foreach($task->subTasks()->completed()->get() as $subTask)
                        <livewire:task.card-completed :task="$subTask" wire:key="task-{{ $task->id }}-subtask-{{$subTask->id}}-completed" />
                    @endforeach
                </x-slot:content>
            </x-collapse>
        @endif
        </div>
@endif
    <div class="flex justify-end">
        <livewire:task.add-task-card :prefilledData="$prefilledNewSubTaskData" label="New sub-task" wire:key="add-subtask-to-task-{{$task->id}}"/>
    </div>
</div>
@endisset
