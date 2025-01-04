<?php

use App\Enums\TaskFrequencies;
use App\Enums\TaskStatuses;
use App\Models\Task;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Modelable;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use OwenIt\Auditing\Events\AuditCustom;

new class extends Component
{
    use Toast;

    #[Modelable]
    public $frequency;

    public bool $openModal = false;
    public Task $task;

    #[On('open-task-delete-modal')]
    public function openModal(int $task_id)
    {
        $task = Task::find($task_id);

        if (!Gate::allows('view-task', $task, auth()->user())) {
            $this->error('Error trying to view this task');
            $this->dispatch('task-view-error');
            return;
        }
        $this->task = $task;
        $this->openModal = true;
    }

    public function deleteTask(): void
    {
        if (!isset($this->task)) {
            $this->error('Error trying to delete this task');
            $this->dispatch('task-delete-error');
            return;
        }
        $res = Gate::inspect('delete-task', $this->task, auth()->user());
        if (!$res->allowed()) {
            $this->openModal = false;
            $this->error($res->message());
            $this->dispatch('task-delete-error');
            return;
        }

        $taskParent = $this->task->taskParent;
        $this->task->update(['status' => TaskStatuses::DELETED->value]);
        if ($taskParent) {
            $taskParent->auditEvent = 'updated';
            $taskParent->isCustomEvent = true;
            $taskParent->auditCustomOld = [
                'subtask_id' => $this->task->id,
            ];
            $taskParent->auditCustomNew = [];
            Event::dispatch(AuditCustom::class, [$taskParent]);
        }
        $this->dispatch('task-deleted');
        $this->dispatch('task-delete', task_id: $this->task->id);
        $this->success('Task(s) deleted successfully');
        $this->openModal = false;
    }
};

?>
<x-modal wire:model="openModal">
    {{-- modal's header --}}
    <div class="flex justify-center items-center p-2 gap-8 flex-col">
        <p class="font-bold text-xl">Are you sure you want to delete this task?</p>
        @isset($task->name)
            <p class="font-bold text-2xl">{{$task->name}}</p>
        @endisset
        <p>Once you do, all its data and the associated subtasks along with their data will be lost</p>
        <div class="flex gap-8 justify-center">
            <x-button label="No, Cancel" @click="$wire.openModal = false"/>
            <x-button class="btn-error" label="Yes, Delete Task" @click="$wire.deleteTask"/>
        </div>
    </div>
</x-modal>
