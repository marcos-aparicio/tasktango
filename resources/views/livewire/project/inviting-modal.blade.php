<?php

use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\User;
use App\Traits\HandlesAuthorization;
use Illuminate\Support\Facades\Event;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use OwenIt\Auditing\Events\AuditCustom;

new class extends Component
{
    use Toast, HandlesAuthorization;

    public string $email;
    public int $selectedOption = 1;
    public bool $openTaskModal = false;
    public Project $project;

    public array $options = [
        ['id' => 1, 'name' => 'For a day'],
        ['id' => 2, 'name' => 'For a week'],
        ['id' => 3, 'name' => 'For a month'],
        ['id' => 4, 'name' => 'Forever'],
    ];

    #[On('inviting-modal-open')]
    public function showModal(Project $project): void
    {
        // TODO:check if the project is valid both in create invitation and here, also check that the user has permissions to create an invitation and to see this project
        $this->project = $project;
        $this->openTaskModal = true;
    }

    public function createInvitation(): void
    {
        $this->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'selectedOption' => 'required|integer|in:' . implode(',', array_column($this->options, 'id')),
        ]);
        $inviter = auth()->user();
        $invitee = User::where('email', $this->email)->first();
        $message = $this->authorizeOrFail('create', [ProjectInvitation::class, $invitee, $this->project]);
        if ($message !== null) {
            $this->addError('email', $message);
            return;
        }

        $until = [
            1 => now()->addDay(),
            2 => now()->addWeek(),
            3 => now()->addMonth(),
            4 => null,
        ];
        $res = $inviter->inviteUserToProject($invitee, $this->project, $until[$this->selectedOption]);
        if ($res['success']) {
            $this->success(title: "{$invitee->username} has been invited to the project");
            $this->reset(['email', 'selectedOption']);
            $this->project->refresh();
            return;
        }
        $this->addError('email', $res['message']);
    }

    public function cancelInvitation(int $invitationId, bool $refresh = true): void
    {
        $message = $this->authorizeOrFail('delete', [ProjectInvitation::class, ProjectInvitation::find($invitationId)]);
        if ($message !== null) {
            $this->addError('custom', $message);
            return;
        }

        $invitation = $this->project->validInvitations()->find($invitationId);
        if ($invitation === null) {
            $this->error('Invitation not found');
            return;
        }
        $invitation->delete();
        // auditing
        $invitation->project->auditEvent = "deleted,invitation,{$invitation->id}";
        $invitation->project->auditCustomOld = [
            'invitee_id' => $invitation->invitee_id,
            'inviter_id' => $invitation->inviter_id,
        ];
        $invitation->project->auditCustomNew = ['invitee_id' => null, 'inviter_id' => null];
        $invitation->project->isCustomEvent = true;
        Event::dispatch(AuditCustom::class, [$invitation->project]);

        $this->success('Invitation has been canceled');
        $this->project->refresh();
    }
};

?>

<x-modal boxClass="max-w-3xl flex flex-col pt-0 px-0" wire:model="openTaskModal">
    {{-- modal's header --}}
    <div class="flex justify-between p-4 pb-2">
        @isset($this->project)
        <p>Invite people to {{$this->project->name}}</p>
        @endisset
        <x-button icon="o-x-mark" class="btn-ghost btn-circle btn-xs" @click="$wire.openTaskModal = false;"responsive/>
    </div>
    <div class="divider divider-primary my-0 h-fit"></div>
    {{-- modal's body --}}
    <x-form class="relative grid grid-cols-1 md:grid-cols-2 gap-4 p-4" wire:submit="createInvitation">
        <div class="col-span-1 flex flex-col gap-4 p-2">
            <p class="text-2xl font-bold">Invite users to project</p>
            <x-input label="Email of the User you want to invite" wire:model="email" first-error-only inline/>
            <x-select label="How long will this invitation be valid for" :options="$options" wire:model="selectedOption"/>
            <div class="flex gap-4">
                <x-button label="Cancel" icon="o-x-mark"  @click="$wire.openTaskModal = false;" responsive></x-button>
                <x-button label="Send Invitation" icon="o-plus" class="btn-primary" type="submit" responsive></x-button>
            </div>
        </div>
        <div class="col-span-1 sticky top-0 max-h-96 flex flex-col gap-4 overflow-y-hidden">
            <p class="text-2xl font-bold flex items-center gap-4">
                Pending Invitations
                @isset($this->project)
                    <span class="!text-lg opacity-75">{{$this->project->validInvitations->count()}}</span>
                @endisset
            </p>
            @if($errors->has('custom'))
                <div class="p-4 bg-error text-white rounded shadow-md">
                    {{$errors->first('custom')}}
                </div>
            @endif
            <div class="overflow-y-scroll flex flex-col gap-2">
                @isset($this->project)
                @forelse($this->project->validInvitations as $invitation)
                    <div class="flex md:flex-col gap-2 p-4 bg-base-200 rounded shadow-md justify-between">
                        <p>
                            <span class="font-bold text-primary">{{$invitation->invitee->username}}</span>
                            was invited to join
                            @isset($invitation->valid_until)
                                <span class="text-sm">Invitation expires on {{$invitation->valid_until->diffForHumans()}}</span>
                            @else
                                <span class="text-sm">Invitation never expires</span>
                            @endisset
                        </p>
                        <div class="flex gap-2">
                            <x-button label="Cancel Invitation" icon="o-x-mark" class="btn-error btn-sm" wire:click="cancelInvitation({{$invitation->id}},false)" responsive tooltip="Cancel invitation"/>
                        </div>
                    </div>
                @empty
                <p class="text-xl">No invitations sent</p>
                @endforelse
                @endisset
            </div>
        </div>

    </x-form>
</x-modal>
