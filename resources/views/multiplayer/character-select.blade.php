@extends('layouts.layout')

@section('title')
    Character Select
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'label' => 'Character Select' ]
    ]) }}
@endsection

@section('content')
    <character-select
        :characters="{{ json_encode($characters) }}"

    ></character-select>
@endsection
