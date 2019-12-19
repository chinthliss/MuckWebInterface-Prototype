@extends('layouts.layout')

@section('title')
    Verify Email
@endsection

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <h4 class="card-header">{{ __('Verify Your Email Address') }}</h4>

                    <div class="card-body">
                        @if (session('resent'))
                            <div class="alert alert-success" role="alert">
                                {{ __('A fresh verification link has been sent to your email address.') }}
                            </div>
                        @endif

                        <p>Before proceeding, please check your email for a verification link.</p>
                        <p>If you did not receive the email, <a href="{{ route('auth.account.resendverifyemail') }}">click here to request another</a>.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
