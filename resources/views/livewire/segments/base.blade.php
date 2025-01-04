<div class="flex flex-col gap-2">
    <div class="sticky top-0  py-2 bg-base-100  z-10">
        <h2 class="font-extrabold text-3xl text-secondary text-center leading-tight">
            {{ $title }}
        </h2>
        <div class="flex justify-end gap-4 items-center">
            @if ($totalTasks->count() > 0)
                <span>{{ $totalTasks->count() }} tasks</span>
            @endif
            @php
                $title = $sort === $SORT_DEFAULT ? 'Default' : ($sort === $SORT_DATE ? 'Due Date' : 'Priority');
            @endphp
            <x-dropdown :label="'Sort by: '.$title" class="btn-ghost btn-sm">
                <x-menu-item title="Default" wire:click="updateSort({{ $SORT_DEFAULT }})"
                    x-bind:class="$wire.sort == {{ $SORT_DEFAULT }} && 'text-primary'" />
                <x-menu-item title="Due Date" wire:click="updateSort({{ $SORT_DATE }})"
                    x-bind:class="$wire.sort == {{ $SORT_DATE }} && 'text-primary'" />
                <x-menu-item title="Priority" wire:click="updateSort({{ $SORT_PRIORITY }})"
                    x-bind:class="$wire.sort == {{ $SORT_PRIORITY }} && 'text-primary'" />
            </x-dropdown>
            <label class="swap swap-flip" x-show="$wire.sort !== {{ $SORT_DEFAULT }} " wire:click="updateTaskList">
                <input type="checkbox" wire:model="ascending" />
                <x-icon name="o-arrow-up" class="swap-on w-5" />
                <x-icon name="o-arrow-down" class="swap-off w-5" />
            </label>
        </div>
    </div>

    <div class="flex flex-col gap-1 items-center" dusk="segment-body">

        @if($taskGroups->count() == 1 && $taskGroups->first() == null)
            <div class="my-4 flex flex-col items-center w-full" wire:key="empty-group-0">
                @if(isset($project) && $project->status == App\Enums\TaskStatuses::COMPLETED)
                    <span class="text-2xl font-bold">🎉 Congratulations!</span>
                    <span>You've completed all tasks in this project</span>
                @else
                    <span class="text-2xl font-bold">🤷 It seems empty around here</span>
                    <span>You've got no tasks! Add one</span>
                @endif
            </div>
        @elseif($taskGroups->count() == 1)
            @forelse($taskGroups->first() as $task)
                <livewire:task.card :task="$task" wire:key="pending-{{ $task->id }}" />
            @empty
                <div class="my-4 flex flex-col items-center w-full" wire:key="empty-group-0">
                    @if(isset($project) && $project->status == App\Enums\TaskStatuses::COMPLETED)
                        <span class="text-2xl font-bold">🎉 Congratulations!</span>
                        <span>You've completed all tasks in this project</span>
                    @else
                        <span class="text-2xl font-bold">🤷 It seems empty around here</span>
                        <span>You've got no tasks! Add one</span>
                    @endif
                </div>
            @endforelse
        @elseif($taskGroups->count() == 0)
            <div class="my-4 flex flex-col items-center w-full" wire:key="empty-group-0">
                @if(isset($project) && $project->status == App\Enums\TaskStatuses::COMPLETED)
                    <span class="text-2xl font-bold">🎉 Congratulations!</span>
                    <span>You've completed all tasks in this project</span>
                @else
                    <span class="text-2xl font-bold">🤷 It seems empty around here</span>
                    <span>You've got no tasks! Add one</span>
                @endif
            </div>
        @else
            @foreach($taskGroups as $group => $tasks)
            @if($group !== '')
            <div class="border-transparent border-b-base-300 border-b-2 rounded-none collapse collapse-arrow overflow-visible" dusk="group-title-[{{$group}}]"
            wire:key="group-title-{{$group}}">
              <input type="checkbox" />
               <div @class(['collapse-title text-xl', 'text-error font-bold' => $group === 'Overdue'])>
                    <p class="text-lg">
                        {{$group}}
                    </p>
                    <p class="text-sm">
                        {{ $tasks->count() }} tasks
                    </p>
                </div>
                <div class="collapse-content flex justify-center flex-col items-center  gap-2">
                @forelse($tasks as $task)
                    <livewire:task.card :$task wire:key="pending-{{ $task->id }}-group-{{$group}}" />
                @empty
                    <div class="my-4 flex flex-col items-center w-full" wire:key="empty-{{$group}}">
                        @if(isset($project) && $project->status == App\Enums\TaskStatuses::COMPLETED)
                            <span class="text-2xl font-bold">🎉 Congratulations!</span>
                            <span>You've completed all tasks in this project</span>
                        @else
                            <span class="text-2xl font-bold">🤷 It seems empty around here</span>
                            <span>You've got no tasks! Add one</span>
                        @endif
                    </div>
                @endforelse
                </div>
            </div>
            @else
                @isset($tasks)
                @foreach($tasks as $task)
                    <livewire:task.card :$task wire:key="pending-{{ $task->id }}-group-{{$group}}" />
                @endforeach
                @endisset
            @endif
            @endforeach
        @endif
    </div>
    <div class="w-full">
        @if(isset($project) && $project->status == App\Enums\TaskStatuses::COMPLETED)
        @else
            <livewire:task.add-task-card :$prefilledData />
        @endif
    </div>

    {{-- task modal ready --}}
    <livewire:task.modal />
    @if ($this->showCompleted && $completedTasks->count() > 0)
        <x-collapse dusk="completed-tasks" collapse-plus-minus>
            <x-slot:heading class="!text-sm flex gap-4">
                <span dusk="title">
                    Completed tasks
                </span>
                <span>
                    {{ $completedTasks->count() }} tasks
                </span>
            </x-slot:heading>
            <x-slot:content class="flex flex-col gap-2 items-end">
                @foreach ($completedTasks as $task)
                    <livewire:task.card-completed :$task wire:key="completed-{{ $task->id }}" />
                @endforeach
            </x-slot:content>
        </x-collapse>
    @endif
</div>
