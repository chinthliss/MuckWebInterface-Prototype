@extends('layouts.layout')

@section('title')
    Login
@endsection

@section('content')
    <auth-account-login></auth-account-login>
    <div class="text-center">
        <div>By logging in, you agree that you are a legal adult wherever you live.</div>
        <div>If you are not a legal adult, please close this site now.</div>
        <div>You will be required to agree to the terms of service before creating or playing a character.</div>
        <div>
            These can be viewed before creating an account from the following link:
            <a href="{{ route('auth.account.termsofservice') }}" target="_blank">View Terms of Service</a>
        </div>
    </div>
@endsection

