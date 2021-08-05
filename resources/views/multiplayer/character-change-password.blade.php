@extends('layouts.layout')

@section('title')
    Change Character Password
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'label' => 'Change Character Password' ]
    ]) }}
@endsection

@section('content')
    <multiplayer-character-password-change
        :errors="{{ $errors }}"
        :characters="{{ json_encode($characters) }}"
    >
    </multiplayer-character-password-change>
@endsection
