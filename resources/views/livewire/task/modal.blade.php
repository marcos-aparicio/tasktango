<?php

use App\Enums\TaskStatuses;
use App\Livewire\Forms\CreateTaskForm;
use App\Models\Project;
use App\Models\Task;
use App\Traits\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;
    // my traits
    use HandlesAuthorization;

    public ?Task $task;

    public CreateTaskForm $form;

    public ?string $prevDueDate;

    public ?string $prevName;

    public bool $completed = false;

    public bool $openTaskModal = false;

    // whether the modal was triggered from the calendar
    public bool $calendarTrigger = false;

    public array $prefilledNewSubTaskData = [];

    #[On(['task-created', 'task-completed'])]
    public function updateSubtasks(): void
    {
        if (!isset($this->task))
            return;
        $this->task = Task::find($this->task->id);
    }

    #[On('open-task-modal')]
    public function openTaskParentModal(): void
    {
        if (!isset($this->task->taskParent))
            return;
        $this->showModal($this->task->taskParent->id);
    }

    public function updatedForm($value, $field)
    {
        // clearing project labels if the user changes the project since project labels are only from that particular project
        if ($field === 'project') {
            $this->form->selected_project_labels = [];
        }
    }

    #[On('open-task-modal')]
    public function showModal(int $task_id, bool $calendar_trigger = false, array $prefilled = null): void
    {
        // task won't exist if it's a new task
        if ($task_id === -1) {
            $this->openTaskModal = true;
            $this->prevDueDate = '';
            $this->prevName = '';
            unset($this->task);
            $this->form->reset();
            $this->form->setData($prefilled);
            return;
        }
        $task = Task::find($task_id);

        if (!Gate::allows('view-task', $task, auth()->user())) {
            $this->error('Error trying to view this task');
            $this->dispatch('task-view-error');
            return;
        }

        $this->calendarTrigger = $calendar_trigger;
        // not using lifecycle updated hook as the task property wont be updated clientside
        // (although the frontend triggers it)
        $this->task = $task;  // this will show the modal after updated
        $this->form->setTask($task);
        $this->completed = $this->task->status->value === TaskStatuses::COMPLETED->value;
        $this->prevDueDate = $this->task->due_date;  // not a task property
        $this->prevName = $this->task->name;  // not a task property
        // $this->task->comments = $this->task->comments()->get();
        $this->openTaskModal = true;
        $this->prefilledNewSubTaskData = ['parent_task_id' => $task->id];
        if (isset($task->project_id))
            $this->prefilledNewSubTaskData['project'] = $task->project_id;
    }

    public function completeTask(bool $value): void
    {
        if (!isset($this->task))
            return;

        if ($value) {
            $this->form->status = TaskStatuses::COMPLETED->value;
            $this->dispatch('task-completed');
        } else {
            $this->form->status = TaskStatuses::PENDING->value;
            $this->dispatch('task-uncompleted');
        }
        $this->task->update(['status' => $this->form->status]);
        $this->task = Task::find($this->task->id);
        $this->form->setTask($this->task);
        $this->completed = $this->task->status->value === TaskStatuses::COMPLETED->value;
        $this->success($this->completed ? 'Task completed successfully' : 'Task marked as pending');
    }

    #[On('task-delete')]
    public function resetData(int $task_id): void
    {
        // you deleted this task
        if (isset($this->task) && $this->task->id === $task_id) {
            $this->task = null;
            $this->form->reset();
            $this->openTaskModal = false;
            return;
        }
        // you deleted a subtask
        $this->updateSubtasks();
    }

    #[On(['comment-added', 'comment-updated', 'comment-deleted'])]
    public function updateTaskComments()
    {
        if (!isset($this->task))
            return;
        $this->task->comments = $this->task->comments()->get();
    }

    public function updatedOpenTaskModal(): void
    {
        if ($this->openTaskModal) {
            return;
        }
        $this->task = null;
        $this->form->reset();
    }

    public function updateOrCreateTask(): void
    {
        $form = $this->form->toArray();

        if (isset($form['due_date']) && $form['due_date'] === '') {
            $form['due_date'] = null;
        }

        if (isset($form['due_date']) && isset($this->prevDueDate) && $form['due_date'] !== $this->prevDueDate)
            $this->form->prevDueDate = null;

        if (isset($form['due_date']) && isset($this->prevDueDate) && $form['due_date'] === $this->prevDueDate)
            $this->form->prevDueDate = $this->prevDueDate;

        $this->validate();
        if (isset($this->task)) {
            $message = $this->authorizeOrFail('update', $this->task);
            if ($message !== null) {
                $this->error($message);
                return;
            }

            $this->form->updateTask($this->task);
            $this->openTaskModal = false;

            $this->dispatch('task-updated.' . $this->task->id);
            if ($this->calendarTrigger && ($this->prevDueDate !== $this->form->due_date || $this->prevName !== $this->form->name)) {
                $this->dispatch('task-updated');
            }
            $this->success('Task updated successfully');
            return;
        }
        $this->form->createTask();
        $this->openTaskModal = false;
        $this->success('Task created successfully');
        $this->dispatch('task-created');
    }

    public function closeModal(): void
    {
        $this->prefilledNewSubTaskData = [];
        $this->dispatch('task-modal-closed');
        $this->openTaskModal = false;
    }
};

?>
<x-modal boxClass="max-w-5xl flex flex-col pt-0 px-0 relative" wire:model="openTaskModal" dusk="task-modal">
    {{-- modal's header --}}
    <div class="top-0 z-50 sticky">
        <div class="flex justify-between p-4 pb-2 bg-base-100">
                @include('livewire.task.partials.breadcrumbs')
                <div>
                @isset($task)
                    <x-dropdown class="bg-transparent border-transparent">
                        <x-slot:trigger>
                            <x-button icon="o-ellipsis-horizontal" class="btn-ghost btn-circle" />
                        </x-slot:trigger>
                        <x-menu-item :title="'Added on ' . $task->created_at->format('M d') . ' · ' . $task->created_at->format('h:i A')"/>
                        <x-menu-item :title="'Created by ' . $task->creatorUsername"/>
                        <x-menu-separator />

                        @include('livewire.task.partials.activity-modal-trigger')
                        <x-menu-item title="Delete" icon="o-trash" @click="$wire.dispatch('open-task-delete-modal',{ task_id: {{$task->id}} });" class="text-error"/>
                    </x-dropdown>
                @endisset
                    <x-button icon="o-x-mark" class="btn-ghost btn-circle" @click="$wire.closeModal" dusk="close" />
                </div>
        </div>
        <div class="divider divider-primary my-0 h-fit"></div>
    </div>

    {{-- modal's body --}}
    <div class="flex flex-col md:grid grid-rows-2 grid-cols-3 py-2 px-4 gap-2">
        {{-- task name, desc and labels --}}
        <div class="col-span-full row-span-1 md:col-span-2 md:row-span-2 flex flex-col gap-2">
            <div class="flex items-center gap-4 pt-2">
            @if(isset($task->id))
                <input type="checkbox" wire:model="completed" x-on:change="(e)=>$wire.completeTask(e.target.checked)"
                    dusk="checkbox"
                    class="checkbox" />
            @endif
                <div class="flex-1">
                    <x-input type="text" placeholder="Enter task" class="font-bold text-2xl border-none"
                        wire:model="form.name" dusk="name"
                        x-bind:class="$wire.form.status === {{ TaskStatuses::COMPLETED->value }} && 'line-through'" />
                </div>
            </div>
            <x-textarea rows="5" placeholder="Description" dusk="description"
                class="border-none focus:border-none focus:ring-transparent leading-5" wire:model="form.description"
                inline />
            @include('livewire.task.partials.subtasks')
            @include('livewire.task.partials.comments')
        </div>
        <div
            class="row-span-1 col-span-3 md:row-span-3 md:col-span-1 md:border-l-primary flex flex-col gap-4 border-2 border-transparent pl-4" dusk="main-attributes">
            <livewire:task.subcomps.priority wire:model="form.priority" />
            <x-datetime label="Due " wire:model="form.due_date" icon="o-calendar" class="input-sm pl-8" dusk="date"/>
            <livewire:task.subcomps.frequency wire:model="form.frequency" />
            @if(isset($form->project) && App\Models\Project::find($form->project)->users->count() > 1)
                <livewire:task.subcomps.assignee wire:model="form.assignee_user_id" :project="\App\Models\Project::find($form->project)" />
            @endif
            <livewire:task.subcomps.projects  wire:model.live="form.project"/>
            @isset($form->project)
                <livewire:task.subcomps.subproject wire:model="form.subproject_id" :project="\App\Models\Project::find($form->project)" />
            @endisset
            <livewire:task.subcomps.user-labels wire:model="form.selected_individual_labels" />
            @isset($form->project)
            @php
                $project = App\Models\Project::find($form->project);
            @endphp
                <livewire:task.subcomps.project-labels wire:model="form.selected_project_labels" :project="$project" />
            @endisset
            <x-button label="Save Data" @click="$wire.updateOrCreateTask" />
        </div>
    </div>
</x-modal>
