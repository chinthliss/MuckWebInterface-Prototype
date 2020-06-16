<?php

namespace Tests\Feature;

use Auth;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class AccountApiTest extends TestCase
{
    use RefreshDatabase;

    /*
    public function testLoginPopulatesMissingApiToken()
    {
        $this->seed();
        $response = $this->json('POST', route('auth.account.login', [
            'email' => 'test@test.com',
            'password' => 'password'
        ]));
        $api = auth()->guard('api');
        // dd($api->getTokenForRequest());
        $token = $api->getTokenForRequest();
        $this->assertNotEmpty($token);
    }
    */

    public function testApiTokenIsGottenFromRequest()
    {
        $this->seed();
        $token = '123';
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->json('GET', '/', [
                'dbref' => '123'
            ]);
        $api = auth()->guard('api');
        $token = $api->getTokenForRequest();
        $this->assertNotEmpty($token);
    }

    /**
     * @depends testApiTokenIsGottenFromRequest
     */
    public function testCorrectApiTokenRetrievesUser()
    {
        $this->seed();
        $token = 'token_testcharacter';
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->json('GET', '/');
        $api = auth()->guard('api');
        $this->assertNotTrue($api->guest());
    }

    /**
     * @depends testApiTokenIsGottenFromRequest
     */
    public function testIncorrectApiTokenDoesNotRetrievesUser()
    {
        $this->seed();
        $token = 'token_badtoken';
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->json('GET', '/');
        $api = auth()->guard('api');
        $this->assertTrue($api->guest());
    }

}
