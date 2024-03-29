@extends('layouts.layout')

@section('title')
    Terms of Service
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'route' => 'auth.account', 'label' => 'Account' ],
        [ 'route' => 'auth.account.termsofservice', 'label' => 'Terms of Service' ]
    ]) }}
@endsection

@section('content')
    <div class="container">
        <h4>Terms of Service</h4>
        <div>
            @foreach ($termsOfService as $line)
                {{ $line }} <br/>
            @endforeach
        </div>
        @auth
            @if ($agreed)
                <div class="p-2 mb-2 bg-primary text-dark">You've agreed to this previously.</div>
            @else
                <div class="border border-primary rounded p-3 text-center">
                    <form action="{{ route('auth.account.termsofservice') }}" method="POST">
                        @csrf
                        <input type="hidden" name="_hash" value="{{ $hash }}">

                        <button type="submit" value="submit" class="btn btn-primary">
                            Click here to agree to the Terms of Service
                        </button>
                    </form>
                </div>
            @endif
        @endauth
    </div>
@endsection
