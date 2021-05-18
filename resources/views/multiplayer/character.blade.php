@extends('layouts.layout')

@section('title')
    View Character
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'label' => 'Multiplayer' ],
        [ 'label' => 'View Character' ]
    ]) }}
@endsection

@section('content')
    <character-profile></character-profile>
@endsection
