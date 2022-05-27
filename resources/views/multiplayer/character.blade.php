@extends('layouts.layout')

@section('title')
    View Character
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'label' => 'View Character' ]
    ]) }}
@endsection

@section('content')
    <character-profile
        :character="{{ json_encode($character->toArray()) }}"
        :controls="{{ $controls }}"
        profile-url="{{ route('multiplayer.character.api', ['name' => $character->name()]) }}"
        avatar-url="{{ route('multiplayer.avatar.render', ['name' => $character->name()]) }}"
        avatar-edit-url="{{ route('multiplayer.avatar') }}"
        :avatar-width="{{ $avatarWidth }}"
        :avatar-height="{{ $avatarHeight }}"
    ></character-profile>
@endsection
