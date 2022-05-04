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
        :avatar-width="{{ $avatarWidth }}"
        :avatar-height="{{ $avatarHeight }}"
        :items="{{ json_encode($items) }}"
        :backgrounds="{{ json_encode($backgrounds) }}"
        :gradients="{{ json_encode($gradients) }}"
        render-url="{{ route('multiplayer.avatar.edit.render') }}"
        state-url="{{ route('multiplayer.avatar.state') }}"
        gradient-url="{{ route('multiplayer.avatar.buygradient') }}"
        item-url="{{ route('multiplayer.avatar.buyitem') }}"
    >
    </avatar-edit>
@endsection
