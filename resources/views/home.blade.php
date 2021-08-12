@extends('layouts.layout')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col">
                <div class="text-center">
                    <h1>Welcome</h1>
                    <p>This is a work-in-progress new site. At some point this whole welcome will be replaced.</p>
                    <p>This new site is also intended to host singleplayer and multiplayer together since, at the moment, there's occasionally confusion with people ending on the multiplayer site and signing up for an account when they just wanted the singleplayer site.</p>
                    <p>Please see the <a href="{{ route('roadmap') }}">Site Roadmap</a> for more information.</p>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12 col-lg-6 border-primary border-lg-right text-center">
                <h2 class="mt-4">Singleplayer</h2>
                <p>Here is where we'll put more information about accessing singleplayer.</p>
                <a class="btn btn-primary" href="{{ route('singleplayer.home') }}">
                    <i class="fas fa-user btn-icon-left"></i>
                    Enter Singleplayer
                </a>
            </div>
            <div class="col-12 col-lg-6 text-center">
                <h2 class="mt-4">Multiplayer</h2>
                <p>Here is where we'll put more information about accessing multiplayer.</p>
                <p>Presently the button will trigger login (if required), then either go to character select or creating a new character if one doesn't exist.</p>
                <a class="btn btn-primary" href="{{ route('multiplayer.home') }}">
                    <i class="fas fa-users btn-icon-left"></i>
                    Enter Multiplayer
                </a>
            </div>
        </div>
    </div>
@endsection
