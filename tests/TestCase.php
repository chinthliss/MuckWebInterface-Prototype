<?php

namespace Tests;

use App\User;
use Auth;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function getValidatedUser(): ?User
    {
        $user = Auth::getProvider()->retrieveById(1);
        return $user instanceof User ? $user : null;
    }

    protected function getPresentUser(): ?User
    {
        $user =  auth()->guard()->user();
        return $user instanceof User ? $user : null;
    }

    // Also returns user for chaining
    protected function loginAsValidatedUser(): ?User
    {
        Auth::loginUsingId('1:');
        return $this->getPresentUser();
    }

}
