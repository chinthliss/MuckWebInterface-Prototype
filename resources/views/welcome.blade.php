@extends('layouts.layout')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col">
                <div class="text-center">
                    <h1>Welcome</h1>
                    <a class="nav-link" href="{{ route('login') }}">Login</a>
                </div>
            </div>
        </div>
    </div>
@endsection
