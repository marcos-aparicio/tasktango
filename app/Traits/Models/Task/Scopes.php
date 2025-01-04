<?php
namespace App\Traits\Models\Task;

use App\Enums\TaskStatuses;

trait Scopes
{
    /**
     * Scope a query to sort tasks by priority and due date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDefaultSort($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->orderByRaw('due_date < NOW() desc, priority ASC, due_date IS NULL, due_date ASC');
    }

    /**
     * Scope a query to only include pending tasks.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', TaskStatuses::PENDING->value);
    }

    /**
     * Scope a query to only include valid(not deleted tasks)
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeValid($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereNot('status', TaskStatuses::DELETED->value);
    }

    /**
     * Scope a query to only include completed tasks.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompleted($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', TaskStatuses::COMPLETED->value);
    }

    /**
     * Scope a query to include only tasks with no parent task.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithoutParent($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereNull('parent_task_id');
    }
}
