<?php

namespace App\Livewire\Segments;

use App\Enums\TaskStatuses;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;
use Mary\Traits\Toast;

class Calendar extends Component
{
    use Toast;

    public ?Project $project;
    public Collection $tasks;
    public array $calendarEvents = [];

    public function updateTaskDueDate(int $id, string $due_date): void
    {
        // TODO: Implement the security/validation features of the method, not allowing due dates in the past
        // correct task id etc
        $task = $this->tasks->find($id);
        $previousDueDate = Carbon::parse($task->due_date);
        $task->due_date = $due_date;
        $task->save();
        if ($previousDueDate->isBefore(today()) && Carbon::parse($due_date)->isAfter(today()))
            $this->success(title: 'Task is not longer overdue');
        $this->dispatch('task-updated');
    }

    public function setEvents(): void
    {
        $whereToQuery = isset($this->project) ? $this->project : auth()->user();
        $this->tasks = $whereToQuery
            ->tasks()
            ->pending()
            ->whereNotNull('due_date')
            ->get();

        $this->calendarEvents = $this
            ->tasks
            ->map(function ($task) {
                $date = $task->due_date;
                $output = [
                    'title' => $task->name,
                    'id' => $task->id,
                    'start' => $date,
                ];
                if (\Carbon\Carbon::parse($date)->isBefore(today())) {
                    $output['title'] = 'Overdue: ' . $output['title'];
                    $output['color'] = 'red';
                    $output['overdue'] = true;
                } else {
                    $output['color'] = 'green';
                }

                return $output;
            })
            ->toArray();
    }

    public function mount(): void
    {
        $this->setEvents();
    }

    public function render()
    {
        $isProject = isset($this->project);
        $projectCompleted = isset($this->project) && $this->project->status == \App\Enums\TaskStatuses::COMPLETED;
        if ($isProject && $projectCompleted) {
            return view('livewire.segments.calendar-completed')
                ->layout('layouts.project');
        }
        return view('livewire.segments.calendar')
            ->layout($isProject ? 'layouts.project' : 'layouts.main');
    }
}
