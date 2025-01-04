<?php

namespace App\Livewire\Forms;

use App\Enums\TaskFrequencies;
use App\Enums\TaskPriorities;
use App\Enums\TaskStatuses;
use App\Models\Project;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Form;
use OwenIt\Auditing\Events\AuditCustom;
use Event;

class CreateTaskForm extends Form
{
    // meaning of tasks that do not have a project, do not change its value
    private $INBOX_PROJECT = null;
    private int $MAX_DEPTH = 4;

    // potentially useful metadata
    public ?string $prevDueDate = null;

    // task properties
    public ?string $name;
    public ?string $description;
    public ?string $due_date;
    public ?int $project;
    public ?int $priority;
    public ?int $frequency;
    public ?int $status;
    public ?int $parent_task_id;
    public ?int $subproject_id = null;
    public ?int $assignee_user_id;
    public ?array $selected_individual_labels = [];
    public ?array $selected_project_labels = [];

    public function rules()
    {
        return [
            'name' => 'required|string|max:'
                . config('constants.tasks.max_name_length'),
            'description' => 'nullable|string|max:'
                . config('constants.tasks.max_description_length'),
            'frequency' => [
                'nullable',
                Rule::enum(TaskFrequencies::class)
            ],
            'priority' => [
                'nullable',
                Rule::enum(TaskPriorities::class)
            ],
            'status' => [
                'nullable',
                Rule::enum(TaskStatuses::class)
            ],
            'due_date' => [
                'nullable',
                'date',
                Rule::requiredIf(fn() =>
                    isset($this->frequency) &&
                    $this->frequency !== null &&
                    $this->frequency !== TaskFrequencies::NONE->value),
                Rule::when($this->prevDueDate === null, 'after_or_equal:today'),
            ],
            'parent_task_id' => [
                'nullable',
                'integer',
                'exists:tasks,id',
                function ($attribute, $value, $fail) {
                    if (!$value)
                        return;

                    $parentTask = Task::find($value);
                    if (!$parentTask)
                        return;

                    if ($parentTask->depth() >= $this->MAX_DEPTH) {
                        $fail('The maximum depth for a task is ' . $this->MAX_DEPTH . '.');
                        return;
                    }

                    if (($parentTask->project->status ?? null) === TaskStatuses::COMPLETED) {
                        $fail('Project is completed.');
                    }

                    if ($parentTask->status === TaskStatuses::COMPLETED) {
                        $fail('You cannot create a task under a parent task that is done. First uncomplete the parent task.');
                    }
                },
            ],
            'assignee_user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    if ($value && $this->project) {
                        $isUserInProject = Project::where('id', $this->project)
                            ->whereHas('users', function ($query) use ($value) {
                                $query->where('user_id', $value);
                            })
                            ->exists();

                        if (!$isUserInProject) {
                            $fail('The assigned user must be a member of the project.');
                        }
                    }
                },
            ],
            'subproject_id' => [
                'nullable',
                'integer',
                'exists:subprojects,id',
                function ($attribute, $value, $fail) {
                    if ($value && !$this->project) {
                        $fail('You must specify a project to create a task under a subproject.');
                    }

                    if ($value && $this->project) {
                        $isSubprojectInProject = Project::where('id', $this->project)
                            ->whereHas('subprojects', function ($query) use ($value) {
                                $query->where('id', $value);
                            })
                            ->exists();

                        if (!$isSubprojectInProject) {
                            $fail('The subproject must be part of the specified project.');
                        }
                    }
                },
            ],
            'selected_project_labels' => 'nullable|array',
            'selected_project_labels.*' => [
                'nullable',
                'integer',
                Rule::exists('labels', 'id')
                    ->where(function ($query) {
                        $query->where('project_id', $this->project);
                    }),
            ],
            'selected_individual_labels' => 'nullable|array',
            'project' => [
                'nullable',
                'integer',
                Rule::exists('project_user', 'project_id')
                    ->where('user_id', auth()->id())
            ],
            'selected_individual_labels.*' => [
                'nullable',
                'integer',
                Rule::exists('labels', 'id')->where('user_id', auth()->id())->whereNull('project_id'),
            ],
        ];
    }

    public function messages()
    {
        return [
            'selected_individual_labels.*' => 'The selected individual label(s) might be invalid.',
            'selected_project_labels.*' => 'The selected project label(s) might be invalid.',
            'due_date.required' => 'The due date field is required because the frequency is selected.',
        ];
    }

    public function setDummyData()
    {
        $this->name = fake()->sentence(2);
        $this->description = fake()->sentence(20);
        $this->due_date = Carbon::now()->format('Y-m-d');
        $this->priority = TaskPriorities::P3->value;
        $this->frequency = TaskFrequencies::WEEKLY->value;
    }

    public function setData(array $data)
    {
        $keys = array_keys($data);
        foreach ($keys as $key)
            $this->$key = $data[$key];
    }

    public function setTask(Task $task)
    {
        $data = $task->toArray();
        $keys = array_keys($data);
        foreach ($keys as $key) {
            if ($key == 'project')
                $this->project = $data[$key]['id'] ?? $this->INBOX_PROJECT;
            else
                $this->$key = $data[$key];

            if ($key == 'due_date')
                $this->prevDueDate = $data[$key];
        }
        $this->subproject_id = $task->subproject->id ?? null;
        $this->assignee_user_id = $task->assignee->id ?? null;
        $this->selected_individual_labels = $task->labelsIndividual()->get()->pluck('id')->toArray();
        $this->selected_project_labels = $task->labelsProject()->get()->pluck('id')->toArray();
        $this->project = $task->project->id ?? $this->INBOX_PROJECT;  // IMPORTANT DO NOT REPLACE THE VALUE WITH ANYTHING ELSE
    }

    /**
     * Assuming data is valid, create a task
     *
     * @return [TODO:type] [TODO:description]
     */
    public function createTask()
    {
        $attributes = $this->toArray();
        // handling empty due date
        if ($attributes['due_date'] == '')
            $attributes['due_date'] = null;
        $task = auth()->user()->tasks()->create($attributes);
        // handling task parent
        if (isset($attributes['parent_task_id'])) {
            $task->taskParent()->associate($attributes['parent_task_id']);
            $taskParent = $task->taskParent;
            $taskParent->auditEvent = 'updated';
            $taskParent->isCustomEvent = true;
            $taskParent->auditCustomOld = [];
            $taskParent->auditCustomNew = [
                'subtask_id' => $task->id,
            ];
            Event::dispatch(AuditCustom::class, [$taskParent]);
        }
        // handling project
        if ($attributes['project'] !== 0 && $attributes['project'] !== null)
            $task->project()->associate($attributes['project']);

        // handling subproject
        if ($attributes['subproject_id'] !== null)
            $task->subproject()->associate($attributes['subproject_id']);

        // handling assignee
        if (isset($attributes['assignee_user_id']))
            $task->assignee()->associate($attributes['assignee_user_id']);

        // handling labels
        $mergedLabels = array_merge($attributes['selected_individual_labels'], $attributes['selected_project_labels']);
        $task->labels()->sync($mergedLabels);
        $task->save();
        $this->reset();
    }

    public function updateTask(Task $task)
    {
        $attributes = $this->toArray();
        // handling empty due date
        if ($attributes['due_date'] == '')
            $attributes['due_date'] = null;
        $task->update($attributes);

        // handling assignee
        if (isset($attributes['assignee_user_id'])) {
            $task->assignee()->associate($attributes['assignee_user_id']);
        } else {
            $task->assignee()->dissociate();
        }
        // handling subproject changes
        if ($attributes['subproject_id'] !== null) {
            $task->subproject()->associate($attributes['subproject_id']);
        } else {
            $task->subproject()->dissociate();
        }

        // handling project changes
        if ($attributes['project'] !== 0 && $attributes['project'] !== null) {
            $task->project()->disassociate();
            $task->project()->associate(Project::find($attributes['project']));
        } else if ($attributes['project'] == null) {
            $task->project()->disassociate();
            $task->subproject()->disassociate();
        }

        // handling label changes
        $existingProjectLabels = $task->labelsProject()->pluck('labels.id')->toArray();
        $mergedLabels = array_merge($attributes['selected_individual_labels'], $attributes['selected_project_labels']);

        // if the project labels are different, we need to audit the change
        if ($attributes['selected_project_labels'] !== $existingProjectLabels) {
            $task->auditSync('labels', $mergedLabels);
        } else {
            $task->labels()->sync($mergedLabels);
        }

        $task->save();
    }
}
