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
        $user =  auth()->user();
        return $user instanceof User ? $user : null;
    }

    // Also returns user for chaining
    protected function loginAsValidatedUser(): ?User
    {
        Auth::loginUsingId('1');
        return $this->getPresentUser();
    }

    // Also returns user for chaining
    protected function loginAsOtherValidatedUser(): ?User
    {
        Auth::loginUsingId('5');
        return $this->getPresentUser();
    }

    // Also returns user for chaining
    protected function loginAsStaffUser(): ?User
    {
        Auth::loginUsingId('6');
        return $this->getPresentUser();
    }

    // Also returns user for chaining
    protected function loginAsAdminUser(): ?User
    {
        Auth::loginUsingId('7');
        return $this->getPresentUser();
    }

    // Also returns user for chaining
    protected function loginAsLockedUser(): ?User
    {
        Auth::loginUsingId('8');
        return $this->getPresentUser();
    }


}
