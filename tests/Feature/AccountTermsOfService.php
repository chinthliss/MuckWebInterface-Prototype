<?php

namespace Tests\Feature;

use App\Http\Middleware\TermsOfServiceAgreed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AccountTermsOfService extends TestCase
{
    use RefreshDatabase;

    public function testCheckSeedIsOkay()
    {
        $this->seed();
        $this->assertDatabaseHas('accounts', [
            'email' => 'test@test.com'
        ]);
        $this->assertDatabaseHas('accounts', [
            'email' => 'notagreedtotos@test.com'
        ]);
    }

    public function testTermsOfServiceViewableWithoutLogin()
    {
        $response = $this->get('/account/termsofservice');
        $response->assertSuccessful();
        $response->assertViewIs('auth.terms-of-service');
    }

    /**
     * @depends testCheckSeedIsOkay
     */
    public function testTermsOfServiceViewableWithLogin()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->get('/account/termsofservice');
        $response->assertSuccessful();
        $response->assertViewIs('auth.terms-of-service');
    }

    /**
     * @depends testCheckSeedIsOkay
     */
    public function testUserWhoHasAcceptedTermsOfServiceContinues()
    {
        $this->seed();
        $user = $this->loginAsValidatedUser();
        $request = Request::create('/home', 'GET');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });
        $middleware = new TermsOfServiceAgreed();
        $response = $middleware->handle($request, function () {
            return 'no redirect';
        });
        $this->assertEquals($response, 'no redirect');
    }

    /**
     * @depends testCheckSeedIsOkay
     */
    public function testUserWhoHasNotAcceptedTermsOfServiceIsRedirected()
    {
        $this->seed();
        $user = Auth::loginUsingId('4');
        $request = Request::create('/home', 'GET');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });
        $middleware = new TermsOfServiceAgreed();
        $response = $middleware->handle($request, function () {
            return 'no redirect';
        });
        $this->assertNotEquals($response, 'no redirect');
    }

    /**
     * @depends testUserWhoHasNotAcceptedTermsOfServiceIsRedirected
     */
    public function testTermsOfServiceCanBeAccepted()
    {
        $this->seed();
        $user = Auth::loginUsingId('4');
        $response = $this->get('/home');
        $response->assertStatus(302);
        $termsOfService = $this->app->make('App\TermsOfService');
        $hash = $termsOfService::getTermsOfServiceHash();
        $response = $this->post(route('auth.account.termsofservice'),
            [
                '_token' => csrf_token(),
                '_hash' => $hash
            ]);
        $this->assertDatabaseHas('account_properties', [
            'aid' => $user->getAid(),
            'propname' => 'tos-hash-viewed',
            'propdata' => $hash
        ]);
    }


}

