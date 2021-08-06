@extends('layouts.layout')

@section('title')
    Avatar
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'label' => 'Avatar' ]
    ]) }}
@endsection

@section('content')
    <div class="container">
        <div class="row">
            <div class="col text-center">
                <h1><i class="fas fa-hammer"></i> To Be Completed <i class="fas fa-hammer fa-flip-horizontal"></i></h1>
            </div>
        </div>
    </div>
@endsection
