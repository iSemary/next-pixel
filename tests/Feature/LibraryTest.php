<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('image-studio'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_image_studio()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('image-studio'));
        $response->assertOk();
    }
}
