@extends('layouts.layout')

@section('title')
    Getting Started - Multiplayer
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'label' => 'Getting Started' ]
    ]) }}
@endsection

@section('content')
    TBC Pending
    Checkboxes of required things like:
    Make an account
    Make a character / Set active character
    Character must be approved


@endsection
