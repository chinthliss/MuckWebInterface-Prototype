@extends('layouts.layout')

@section('title')
    Change Active Character
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'label' => 'Multiplayer' ],
        [ 'label' => 'Character Select' ]
    ]) }}
@endsection

@section('content')
    <character-select
        :characters="{{ json_encode($characters) }}"
    ></character-select>
@endsection
