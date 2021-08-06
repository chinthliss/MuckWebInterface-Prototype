@extends('layouts.layout')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col">
                <div class="text-center">
                    <h1>Welcome</h1>
                    <p>The long term intent is to bring both singleplayer and multiplayer into one site.</p>
                    <p>At the moment there are instances, for example, where people sign up for multiplayer but just want the singleplayer content.</p>
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
