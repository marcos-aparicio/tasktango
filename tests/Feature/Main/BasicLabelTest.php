<?php

namespace Tests\Feature\Main;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BasicLabelTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cant_access_other_people_labels(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $labelUser1 = $user1->labels()->create(['name' => 'User 1 Label']);

        $response = $this->actingAs($user2)->get(route('label', $labelUser1));

        $response
            ->assertStatus(403);
    }

    public function test_user_can_access_its_own_labels(): void
    {
        $user = User::factory()->create();
        $labelUser = $user->labels()->create(['name' => 'User 1 Label']);

        $response = $this->actingAs($user)->get(route('label', $labelUser));

        $response
            ->assertOk()
            ->assertSee('User 1 Label');
    }
}
