<?php

namespace App\Models;

use App\Enums\TaskFrequencies;
use App\Enums\TaskPriorities;
use App\Enums\TaskStatuses;
use App\Traits\Models\Task\Relations;
use App\Traits\Models\Task\Scopes;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use OwenIt\Auditing\Contracts\Auditable;

class Task extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use HasFactory;
    /* my traits */
    use Scopes, Relations;

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // actually stablishing the default values
        static::creating(function ($query) {
            $query->status = $query->status ?? TaskStatuses::PENDING;
            $query->priority = $query->priority ?? TaskPriorities::P5;
            $query->order = $query->order ?? 0;
            $query->frequency = $query->frequency ?? TaskFrequencies::NONE;
            $query->due_date = $query->due_date ?? null;
            $query->project_id = $query->project_id === 0 ? null : $query->project_id ?? null;
            $query->parent_task_id = $query->parent_task_id ?? null;
            $query->description = $query->description ?? null;
        });
        static::updating(function ($query) {
            // Check if the status has changed to the specified value
            if ($query->isDirty('status') && ($query->status === TaskStatuses::COMPLETED || $query->status === TaskStatuses::DELETED)) {
                $query->updateChildTasksStatus($query->status);
            }

            if ($query->status !== TaskStatuses::COMPLETED || $query->frequency === TaskFrequencies::NONE || $query->frequency === null)
                return;

            if ($query->frequency === TaskFrequencies::NONE || $query->frequency === null)
                return;

            $date = Carbon::parse($query->due_date);
            $date = $date->isPast() ? Carbon::now() : $date;

            if ($query->frequency === TaskFrequencies::DAILY)
                $query->due_date = $date->addDays(1)->format('Y-m-d');
            else if ($query->frequency === TaskFrequencies::WEEKLY)
                $query->due_date = $date->addWeeks(1)->format('Y-m-d');
            else if ($query->frequency === TaskFrequencies::BIWEEKLY)
                $query->due_date = $date->addWeeks(2)->format('Y-m-d');
            else if ($query->frequency === TaskFrequencies::MONTHLY)
                $query->due_date = $date->addMonths(1)->format('Y-m-d');

            $query->status = TaskStatuses::PENDING;
        });

        static::deleting(function ($task) {
            // delete all comments related to the task
            $task->labels()->detach();
            $task->comments()->each(fn($c) => $c->delete());
            // delete all subtasks related to the task
            $task->subTasks()->each(fn($t) => $t->delete());
            // TODO: do not allow a non project task be a subchild or parent of a project task
        });
    }

    /**
     * Recursively update the status of child tasks.
     *
     * @param string $status
     * @return void
     */
    protected function updateChildTasksStatus(TaskStatuses $status): void
    {
        foreach ($this->subTasks()->get() as $childTask) {
            $childTask->status = $status;
            $childTask->save();
        }
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'due_date',
        'priority',
        'frequency',
        'status',
        'order',
    ];

    protected function dueDate(): Attribute
    {
        return Attribute::make(
            get: fn(mixed $value) => $value == null ? null : Carbon::parse($value)->format('Y-m-d'),
        );
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'date:Y-m-d',
            'status' => TaskStatuses::class,
            'frequency' => TaskFrequencies::class,
            'priority' => TaskPriorities::class,
        ];
    }

    /**
     * returns the depth of the task
     *
     * @return int how deep the task is in the hierarchy
     */
    public function depth(): int
    {
        $depth = 0;
        $current = $this;
        while ($current->taskParent) {
            $depth++;
            $current = $current->taskParent;
        }
        return $depth;
    }
}
