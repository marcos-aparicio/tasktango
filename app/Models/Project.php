<?php

namespace App\Models;

use App\Enums\ProjectUserRoles;
use App\Enums\TaskStatuses;
use App\Traits\Models\Project\Actions;
use App\Traits\Models\Project\Relations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Project extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use HasFactory;
    // my traits
    use Relations, Actions;

    protected $fillable = [
        'name',
        'description',
        'status'
    ];

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
            $query->description = $query->description == '' ? null : $query->description ?? null;
        });

        static::deleting(function ($project) {
            // deleting all tasks related to the project
            $project->tasks()->each(fn($t) => $t->delete());
            // delete all labels related to the project
            $project->users()->detach();
            // delete all labels related to the project
            $project->labels()->each(fn($l) => $l->delete());
            // delete all invitations related to the project
            $project->invitations()->each(fn($i) => $i->delete());
            // delete all notes related to the project
            $project->notes()->each(fn($n) => $n->delete());
            // delete all subprojects related to the project
            $project->subprojects()->each(fn($sp) => $sp->delete());
        });
    }

    protected function casts(): array
    {
        return [
            'status' => TaskStatuses::class,
        ];
    }
}
