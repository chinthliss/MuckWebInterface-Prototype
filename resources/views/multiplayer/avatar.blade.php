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
    <avatar-edit
        :items="{{ json_encode($items) }}"
        :backgrounds="{{ json_encode($backgrounds) }}"
        :gradients="{{ json_encode($gradients) }}"
        render-url="{{ route('multiplayer.avatar.edit.render') }}"
        :avatar-width="{{ $avatarWidth }}"
        :avatar-height="{{ $avatarHeight }}"
        :starting="{{ json_encode($starting) }}"

    >
    </avatar-edit>
@endsection
