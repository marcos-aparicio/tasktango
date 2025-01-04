<?php

use App\Models\Project;
use App\Traits\HandlesAuthorization;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast;
    use HandlesAuthorization;

    #[Locked]
    public Project $project;

    public string $selectedTab = 'not-task-tab';

    public bool $showModal = false;

    public function getTaskAudits()
    {
        $audits = collect();

        foreach ($this->project->tasks as $task) {
            $audits = $audits->merge($task->audits);
        }

        return $audits;
    }

    #[On('open-project-activity-modal')]
    public function showModal(): void
    {
        $this->showModal = true;
    }
};
?>
@php
$propertyMapper = [
    'name' => 'Project Name',
    'description' => 'Project Description',
];
@endphp
<x-modal title="Project Activity" wire:model="showModal" boxClass="flex flex-col max-w-4xl">
    <div class="overflow-y-scroll flex-1 h-full">
        <x-tabs wire:model="selectedTab">
            <x-tab name="task-tab" label="Task-Related">
                @include('livewire.project.partials.activity.task-related-audits')
            </x-tab>
            <x-tab name="not-task-tab" label="Not Task-Related">
                @include('livewire.project.partials.activity.not-task-related-audits')
            </x-tab>
        </x-tabs>
    </div>
</x-modal>
