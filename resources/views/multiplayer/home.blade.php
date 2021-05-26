@extends('layouts.layout')

@section('title')
    Multiplayer Dashboard
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'label' => 'Multiplayer' ]
    ]) }}
@endsection

@section('content')
    <multiplayer-dashboard
        :characters="{{ json_encode($characters) }}"
        character-select-url="{{$characterSelectUrl}}"
    ></multiplayer-dashboard>
@endsection
