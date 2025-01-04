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

    public function openModal()
    {
        $this->dispatch('open-task-modal', task_id: $this->task->id);
    }
}
?>

<div class="bg-base-300 p-2 rounded-xl items-center flex w-full md:w-2/3 gap-4 min-h-20 md:min-h-12 cursor-pointer opacity-75" @click="$wire.openModal" dusk="completed-task-card-{{$task->id}}">
    <p class="font-bold text-ellipsis ... line-clamp-1 line-through">{{ $task->name }}</p>
</div>
