@extends('layouts.layout')

@section('title')
    Character Generation
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'label' => 'Character Generation' ]
    ]) }}
@endsection

@section('content')
    <character-initial-setup
        :config="{{ json_encode($config) }}"
        :errors="{{ $errors }}"
        :old="{{ json_encode(old(), JSON_FORCE_OBJECT) }}"
    ></character-initial-setup>
@endsection
