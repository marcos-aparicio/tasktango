<?php

namespace App\Models;

use App\Traits\Models\User\Actions;
use App\Traits\Models\User\Relations;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Lab404\Impersonate\Models\Impersonate;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, Impersonate;
    // My Traits
    use Relations, Actions;

    protected $table = 'users';

    public function canImpersonate()
    {
        return $this->is_super_admin;
    }

    public function canBeImpersonated()
    {
        return !$this->is_super_admin;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            // creating user profile picture
            if (env('SUPER_ADMIN_USERNAME') !== null &&
                    $user->is_super_admin &&
                    $user->username !== env('SUPER_ADMIN_USERNAME')) {
                $user->is_super_admin = false;
            }
        });
        static::updating(function ($user) {
            // updating user profile picture
        });
        static::deleting(function ($user) {
            // deleting user projects in which the user is the owner
            $user->ownedProjects()->each(fn($project) => $project->delete());
            // deleting individual tasks
            $user->individualTasks()->each(fn($t) => $t->delete());
            // deleting individual labels
            $user->individualLabels()->each(fn($l) => $l->delete());
            // deleting received project invitations
            $user->receivedProjectInvitations()->each(fn($i) => $i->delete());
            $user->notOwnedProjects()->each(fn($project) => $project->users()->detach($user->id));
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'full_name',
        'email',
        'username',
        'password',
        'provider',
        'provider_id',
        'provider_token',
        'profile_picture',
        'has_asked_for_profile_picture',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the validation rules that apply to user registration.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public static function getRules()
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:30', 'unique:users'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                'unique:users'
            ],
            'password' => [
                'required_if:provider,null',
                'required_if:provider_id,null',
                'confirmed',
                Password::defaults(),
                'min:' . config('constants.password_min_length'),
                'max:' . config('constants.password_max_length'),
            ],
            'provider' => ['string', 'required_if:password,null'],
            'provider_id' => [['string', 'required_if:password,null']],
        ];
    }

    /**
     * Returns a valid URL of the user's profile picture.
     * Returns an empty string if the user has no profile picture.
     *
     * @return string valid URL or URI of the user's profile picture to use in the frontend
     */
    public function getProfilePictureUrl(): string|null
    {
        if ($this->profile_picture === null) {
            return null;
        }

        return Storage::url($this->profile_picture);
    }

    /**
     * Determine if the user is using an OAuth account or registered manually.
     *
     * @return bool true if the user is using an OAuth account, false otherwise
     */
    public function isOauthAccount(): bool
    {
        return $this->provider !== null &&
            $this->provider_id !== null &&
            $this->provider_token !== null &&
            $this->password === null;
    }
}
