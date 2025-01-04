<?php

namespace App\Livewire\Segments;

use App\Models\Project;
use Illuminate\Support\Collection;

class Today extends BaseSegment
{
    // value that can be passed through although optional
    public ?Project $project;
    protected bool $showCompleted = false;

    protected function setPrefilledData(): array
    {
        return [
            'due_date' => now()->format('Y-m-d'),
            'project' => $this->project->id ?? null,
        ];
    }

    protected function setTitle(): string
    {
        return 'Today';
    }

    protected function taskFilteringCriteria(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        $whereToQuery = isset($this->project) ? $this->project->tasks() : auth()->user()->tasks();
        return $whereToQuery->whereDate('due_date', '<=', today());
    }

    protected function applyGrouping(Collection $tasks)
    {
        $groupedTasks = collect();

        $overdueTasks = $tasks->filter(function ($task) {
            return \Carbon\Carbon::parse($task->due_date)->isBefore(today());
        });
        if ($overdueTasks->count() > 0)
            $groupedTasks->put('Overdue', $overdueTasks);

        $groupedTasks->put(now()->format('M j - l'), $tasks->filter(function ($task) {
            return \Carbon\Carbon::parse($task->due_date)->isToday();
        }));
        return $groupedTasks;
    }
}
