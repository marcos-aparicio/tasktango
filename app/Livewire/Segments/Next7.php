<?php

namespace App\Livewire\Segments;

use App\Models\Project;
use Illuminate\Support\Collection;

class Next7 extends BaseSegment
{
    protected bool $showCompleted = false;
    public ?Project $project;

    protected function setPrefilledData(): array
    {
        return [
            'due_date' => now()->format('Y-m-d'),
            'project' => $this->project->id ?? null,
        ];
    }

    protected function applyGrouping(Collection $tasks)
    {
        $allDates = collect();
        $today = now();

        for ($i = 0; $i <= 7; $i++) {
            $date = $today->copy()->addDays($i);
            $formattedDate = $date->format('M j - l');
            if ($date->year !== now()->year) {
                $formattedDate .= ' ' . $date->year;
            }
            $allDates->put($formattedDate, collect());
        }

        $groupedTasks = $tasks->groupBy(function ($task) {
            $date = \Carbon\Carbon::parse($task->due_date);
            $formattedDate = $date->format('M j - l');
            if ($date->year !== now()->year) {
                $formattedDate .= ' ' . $date->year;
            }
            return $formattedDate;
        });

        $allDates = $allDates->map(function ($value, $formattedDate) use ($groupedTasks) {
            return $groupedTasks->get($formattedDate, collect());
        });

        // Add overdue tasks
        $overdueTasks = $tasks->filter(function ($task) {
            return \Carbon\Carbon::parse($task->due_date)->isBefore(today());
        });

        if ($overdueTasks->isNotEmpty())
            $allDates->prepend($overdueTasks, 'Overdue');

        return $allDates;
    }

    protected function setTitle(): string
    {
        return 'Next 7 Days';
    }

    protected function taskFilteringCriteria(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        $whereToQuery = auth()->user();
        if (isset($this->project))
            $whereToQuery = $this->project;
        // return $whereToQuery->tasks()->whereBetween('due_date', [now()->subDay(), now()->addDays(7)]);
        return $whereToQuery->tasks()->where('due_date', '<=', now()->addDays(7));
    }
}
