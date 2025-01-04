<?php

namespace App\Livewire\Segments;

use App\Models\Project;
use Illuminate\Support\Collection;

class ProjectIndex extends BaseSegment
{
    public ?Project $project;

    protected function setPrefilledData(): array
    {
        return ['project' => $this->project->id];
    }

    protected function setTitle(): string
    {
        return 'Index: ' . $this->project->name;
    }

    protected function taskFilteringCriteria(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->project->tasks();
    }

    protected function applyGrouping(Collection $tasks)
    {
        $groupedTasks = $tasks->groupBy(function ($task) {
            return $task->subproject->name ?? '';
        });

        $emptyGroup = $groupedTasks->pull('');
        return collect(['' => $emptyGroup])->merge($groupedTasks);
    }
}
