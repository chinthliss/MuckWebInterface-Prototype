@extends('layouts.layout')

@section('title')
    {{ $character->name() }} - Profile
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'label' => 'Characters' ],
        [ 'label' => $character->name() ]
    ]) }}
@endsection

@section('content')
    <character-profile
        :character="{{ json_encode($character->toArray()) }}"
        :controls="{{ $controls }}"
        profile-url="{{ $profileUrl }}"
        avatar-url="{{ $avatarUrl }}"
        :avatar-width="{{ $avatarWidth / 2 }}"
        :avatar-height="{{ $avatarHeight / 2 }}"
    ></character-profile>
@endsection
