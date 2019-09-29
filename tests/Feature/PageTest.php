<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PageTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testPageContainsMessage()
    {
        $this->withSession(['message-success' => 'success message test']);
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSeeText('success message test');

    }
}
