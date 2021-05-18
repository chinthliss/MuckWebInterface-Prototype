@extends('layouts.layout')

@section('content')
    <div class="row">
        <div class="col">
            <div class="text-center">
                <h1>Welcome</h1>
                <h2>Singleplayer</h2>
                <p>Here is where we'll put more information about accessing singleplayer.</p>
                <h2>Multiplayer</h2>
                <p>Here is where we'll put more information about accessing multiplayer.</p>
                <p>Maybe move content about getting a telnet client here?</p>
                <a class="btn btn-primary" href="{{ route('multiplayer.home') }}">Enter Multiplayer</a>
            </div>
        </div>
    </div>
@endsection
