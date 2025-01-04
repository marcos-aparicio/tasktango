<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class ProjectInvitation extends Model
{
    protected $fillable = [
        'valid_until',
        'accepted_at',
        'rejected_at',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function invitee()
    {
        return $this->belongsTo(User::class, 'invitee_id');
    }

    public function inviter()
    {
        return $this->belongsTo(User::class, 'inviter_id');
    }

    protected function casts(): array
    {
        return [
            'valid_until' => 'datetime',
        ];
    }
}
