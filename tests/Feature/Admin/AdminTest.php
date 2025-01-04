<?php

namespace Tests\Feature\Admin;

use App\Models\Project;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_admin_site_and_logout(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => 1,
            'username' => 'super_admin_user',
        ]);

        $this->actingAs($admin);

        $this
            ->get(route('index'))
            ->assertRedirect(route('admin'));

        // Simulate logout
        $this->get(route('logout'));

        // Assert that the user is no longer authenticated
        $this->assertGuest();
        $this->assertDatabaseHas('users', [
            'username' => 'super_admin_user',
            'is_super_admin' => 1,
        ]);
    }

    public function test_admin_cannot_access_normal_routes_without_impersonation(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => 1,
            'username' => 'super_admin_user',
        ]);

        $this->actingAs($admin);
        $this->get(route('index'))->assertDontSee('Inbox');
        $this->get(route('labels'))->assertRedirect('admin');
        $this->get(route('projects'))->assertRedirect('admin');
        $this->get(route('today'))->assertRedirect('admin');
        $this->get(route('next-7-days'))->assertRedirect('admin');
        $this->get(route('calendar'))->assertRedirect('admin');
    }

    public function test_admin_can_impersonate_normal_user(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => 1,
            'username' => 'super_admin_user',
        ]);

        $user = User::factory()->create([
            'username' => 'normal_user',
        ]);

        $this->actingAs($admin);

        $this
            ->get(route('impersonate', $user->id));

        $this->assertDatabaseHas('users', [
            'username' => 'super_admin_user',
            'is_super_admin' => 1,
        ]);

        $this->assertDatabaseHas('users', [
            'username' => 'normal_user',
            'is_super_admin' => 0,
        ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_can_impersonate_normal_user_and_logout(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => 1,
            'username' => 'super_admin_user',
        ]);

        $user = User::factory()->create([
            'username' => 'normal_user',
        ]);

        $this->actingAs($admin);

        $this
            ->get(route('impersonate', $user->id));

        $this->assertDatabaseHas('users', [
            'username' => 'super_admin_user',
            'is_super_admin' => 1,
        ]);

        $this->assertDatabaseHas('users', [
            'username' => 'normal_user',
            'is_super_admin' => 0,
        ]);

        $this->assertAuthenticatedAs($user);

        $this->get(route('today'))->assertSee('Today');
        $this->get(route('next-7-days'))->assertSee('Next 7 days');
        $this->get(route('calendar'))->assertSee('Calendar');
        $this->followingRedirects()->get(route('index'))->assertSee('Inbox');
        $this->get(route('labels'))->assertSee('Labels');
        $this->get(route('projects'))->assertSee('Projects');

        // Simulate logout
        $this->get(route('logout'));

        // Assert that the user is no longer authenticated
        $this->assertGuest();
        $this->assertDatabaseHas('users', [
            'username' => 'super_admin_user',
            'is_super_admin' => 1,
        ]);
        $this->assertDatabaseHas('users', [
            'username' => 'normal_user',
            'is_super_admin' => 0,
        ]);
    }

    public function test_can_delete_users()
    {
        $admin = User::factory()->create([
            'is_super_admin' => 1,
            'username' => 'super_admin_user',
        ]);

        $users = User::factory()
            ->count(10)
            ->create();
        $this->actingAs($admin);

        $adminUsersComponent = Volt::test('admin.users-table')
            ->call('deleteUser', $users[0]->id);
        $this->assertDatabaseMissing('users', [
            'id' => $users[0]->id,
        ]);

        $adminUsersComponent = Volt::test('admin.users-table')
            ->call('deleteUser', $users[2]->id);
        $this->assertDatabaseMissing('users', [
            'id' => $users[2]->id,
        ]);
    }

    public function test_can_delete_project()
    {
        $admin = User::factory()->create([
            'is_super_admin' => 1,
            'username' => 'super_admin_user',
        ]);

        $this->seed(DatabaseSeeder::class);

        $project = Project::first();
        $adminUsersComponent = Volt::test('admin.projects-table')
            ->assertSee('Project')
            ->call('deleteProject', $project->id);

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
        $this->assertDatabaseMissing('tasks', ['project_id' => $project->id]);
    }
}
