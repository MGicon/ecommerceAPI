<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class Hello extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    private User $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUser();
    }
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    private function createUser()
    {
        $user = User::factory()->create();
        return ;
    }
}
