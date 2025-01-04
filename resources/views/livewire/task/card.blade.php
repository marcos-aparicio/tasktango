<?php

// important enum to import for the blade template
use App\Enums\TaskFrequencies;
use App\Enums\TaskStatuses;
use App\Models\Task;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast;

    public Task $task;
    public ?string $class;
    public bool $completed = false;

    public function completeTask()
    {
        if (!$this->completed)
            return;
        $this->task->status = TaskStatuses::COMPLETED;
        $this->task->save();
        $this->dispatch('task-completed', id: $this->task->id);
        // refresh task
        $this->refreshTask();
        $this->success('Task completed successfully');
    }

    #[On('task-updated.{task.id}')]
    public function refreshTask()
    {
        $this->task = Task::find($this->task->id);
        $this->completed = $this->task->status->value === TaskStatuses::COMPLETED->value;
        $this->dispatch('task-updated', id: $this->task->id);
    }

    public function openModal()
    {
        $this->dispatch('open-task-modal', task_id: $this->task->id);
    }
}
?>

<div
    class="bg-base-300 px-4 py-1 rounded-xl items-center flex w-full md:w-2/3 gap-4 min-h-20 md:min-h-12 cursor-pointer {{ $class }}"
    @click="$wire.openModal" dusk="task-card-{{$task->id}}"
>
    <div @click="e=>e.stopPropagation()">
        <input type="checkbox" wire:model="completed" wire:change="completeTask" class="checkbox" dusk="checkbox"/>
    </div>
    <div class="flex flex-col self-start gap-0.5 flex-1 overflow-hidden">
        <p class="font-bold text-ellipsis ... line-clamp-1 overflow-hidden">{{ $task->name }}</p>
        <p class="font-secondary opacity-70 text-sm text-ellipsis ... line-clamp-2">{{ $task->description }}</p>
        <div class="flex gap-4">
        @if($task->project)
        @php
            $label = $task->project->name;
            if(strlen($label) > 20)
                $label = substr($label,0,20).'...';

            if(isset($task->subproject->name))
                $label .= ' / '.$task->subproject->name;

        @endphp
            <x-icon name="o-folder" class="w-4 h-4 opacity-80 text-sm text-primary" :$label />
        @endif
        @if($task->subTasks()->valid()->count() > 0)
            <x-icon name="o-list-bullet" class="w-4 h-4 opacity-80 text-sm"
            label="{{$task->subTasks()->completed()->count()}}/{{$task->subTasks()->valid()->count()}}" />
        @endif
        @if($task->comments()->count() > 0)
            <x-icon name="o-chat-bubble-left-right" class="w-4 h-4 opacity-80 text-sm"
            label="{{$task->comments()->count()}}" />
        @endif
        @if($task->labelsIndividual()->count() > 0)
            <div class="flex gap-2 py-1 text-primary max-w-20 md:max-w-48 overflow-x-hidden truncate ...">
                @foreach ($task->labelsIndividual()->get()->take(3) as $label)
                    <x-icon name="o-tag" :label="$label->name" class="text-xs w-4 h-4 truncate ..." wire:key="task-{{$task->id}}-{{ Str::random(20) }}" />
                @endforeach
                @if($task->labelsIndividual()->count() > 3)
                    <span>...</span>
                @endif
            </div>
        @endif
        @if($task->labelsProject()->count() > 0)
            <div class="flex gap-2 py-1 text-secondary max-w-20 md:max-w-48 overflow-x-hidden truncate ...">
                @foreach ($task->labelsProject()->get()->take(3) as $label)
                    <x-icon name="o-tag" :label="$label->name" class="text-xs w-4 h-4 truncate ..." wire:key="{{ Str::random(20) }}" />
                @endforeach
                @if($task->labelsProject()->count() > 3)
                    <span>...</span>
                @endif
            </div>
        @endif
        </div>
    </div>

    <div class="flex flex-col justify-end items-end self-end mr-auto text-sm gap-0.5">
        <div class="flex justify-end gap-4">
            @if($task->frequency != TaskFrequencies::NONE)
                <p class="font-bold flex justify-center gap-2">
                    <x-icon name="o-arrow-path" />
                    <span>{{config('constants.tasks.frequencies')[$task->frequency->value]}}</span>
                </p>
            @endif
            <p class="font-bold flex justify-end items-center gap-2 text-{{  config('constants.tasks.priority_colors')[$task->priority->value]  }}">
                <x-icon name="s-flag" class="w-3 h-3"/>
                <span>{{$task->priority}}</span>
            </p>
        </div>
        @if ($task->due_date)
            <x-icon name="o-calendar" :label="$task->due_date" @class(["font-bold flex justify-end gap-2","text-error" => $task->due_date < today()->subDay(1), "text-success"=> \Carbon\Carbon::parse($task->due_date)->isSameDay(today()) ])/>
        @endif

        @isset($task->assignee)
            <div class="flex gap-2 py-1 text-primary max-w-20 md:max-w-48 overflow-x-hidden truncate ... items-center">
                <x-avatar-or-icon :user="$task->assignee" icon-class="text-xs w-4 h-4" avatar-class="!w-4 !h-4 object-contain border-primary border-2"/>
                <span class="text-xs">{{$task->assignee->username}}</span>
            </div>
        @endisset
    </div>
    <div @click="e=>e.stopPropagation()">
        <x-custom-dropdown class="bg-base-300 border-transparent" dropdown-class="z-[200]">
            <x-slot:trigger>
                <x-button icon="o-ellipsis-horizontal" class="btn-ghost btn-circle btn-xs" />
            </x-slot:trigger>
            <x-menu-item :title="'Added on ' . $task->created_at->format('M d') . ' · ' . $task->created_at->format('h:i A')"/>
            <x-menu-separator />
            @include('livewire.task.partials.activity-modal-trigger')
            <x-menu-item title="Edit" icon="o-pencil-square" @click="$wire.openModal"/>
            <x-menu-item title="Delete" icon="o-trash" @click="$wire.dispatch('open-task-delete-modal',{ task_id: {{$task->id}} });" class="text-error"/>
        </x-custom-dropdown>
    </div>
</div>
