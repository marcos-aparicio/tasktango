<?php

namespace App\Livewire\Segments;

use App\Models\Project;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

abstract class BaseSegment extends Component
{
    private int $SORT_DEFAULT = 1;
    private int $SORT_DATE = 2;
    private int $SORT_PRIORITY = 3;

    // by default it will be only one group holding all tasks
    public ?Project $project;
    public Collection $taskGroups;
    public Collection $totalTasks;
    public Collection $completedTasks;
    public int $sort = 1;  // make sure to set it as default at first
    public $title;
    public $ascending = true;  // doesnt apply for default sort
    public array $prefilledData = [];

    // optional properties that can be set from child classes
    protected bool $showCompleted = true;

    protected function applyGrouping(Collection $tasks)
    {
        return collect($tasks->groupBy(''));
    }

    #[On(['task-created', 'task-deleted', 'task-completed', 'task-uncompleted'])]
    public function updateTaskList()
    {
        $query = $this->taskFilteringCriteria()->pending();
        if ($this->showCompleted)
            $this->completedTasks = $this->taskFilteringCriteria()->completed()->get();

        // sorting the tasks
        if ($this->sort == $this->SORT_DEFAULT) {
            $this->totalTasks = $query->defaultSort()->get();
        } elseif ($this->sort == $this->SORT_DATE) {
            // i know this is the reverse but it is expected to be this way
            $this->totalTasks = $query->orderBy('due_date', $this->ascending ? 'desc' : 'asc')->orderBy('priority')->get();
        } elseif ($this->sort == $this->SORT_PRIORITY) {
            $this->totalTasks = $query->orderBy('priority', $this->ascending ? 'asc' : 'desc')->orderBy('due_date')->get();
        } else {
            $this->totalTasks = $query->defaultSort()->get();
        }

        $this->taskGroups = $this->applyGrouping($this->totalTasks);
    }

    abstract protected function taskFilteringCriteria();
    abstract protected function setTitle(): string;
    abstract protected function setPrefilledData(): array;

    public function updateSort(int $new_sort_value)
    {
        try {
            $this->sort = $new_sort_value;
            $this->updateTaskList();
        } catch (\Exception $e) {
        }
    }

    #[On(['task-updated'])]
    public function updateTaskListIfDueDateChanged(int $id)
    {
        $this->updateTaskList();
    }

    public function mount(): void
    {
        $this->title = $this->setTitle();
        $this->updateTaskList();
        $this->prefilledData = $this->setPrefilledData();
    }

    public function render()
    {
        $isProject = isset($this->project);
        return view('livewire.segments.base', [
            'SORT_DEFAULT' => $this->SORT_DEFAULT,
            'SORT_DATE' => $this->SORT_DATE,
            'SORT_PRIORITY' => $this->SORT_PRIORITY,
            'project' => $isProject ? $this->project : null,
        ])->layout($isProject ? 'layouts.project' : 'layouts.main');
    }
}
