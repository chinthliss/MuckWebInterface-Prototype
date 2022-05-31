@extends('layouts.layout')

@section('title')
    Verify Email
@endsection

@section('content')
    <div class="container">
        <h4>Verify Your Email Address</h4>
        <div class="row">
            <div class="col">
                <p>Before proceeding, please check your email for a verification link.</p>
            </div>
        </div>
        @if (session('resent'))
            <div class="row">
                <div class="col alert alert-success" role="alert">
                    {{ __('A fresh verification link has been sent to your email address.') }}
                </div>
            </div>
        @else
            <div class="row">
                <div class="col">
                    <p>If you did not receive the email, <a href="{{ route('auth.account.resendverifyemail') }}">click
                            here to request another</a>.</p>
                    <p>If you are migrating from the old website it won't have sent the email,
                        so you will need to use the link above to send an email.</p>
                </div>
            </div>
        @endif
    </div>
@endsection
