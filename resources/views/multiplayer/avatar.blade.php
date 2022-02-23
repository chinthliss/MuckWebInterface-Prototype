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
        :present-customizations = "{{ json_encode($presentCustomizations) }}"
        :gradients="{{ json_encode($gradients) }}"
        render-url = "{{ route('multiplayer.avatar.edit.render') }}"
        :avatar-width = "{{ $avatarWidth }}"
        :avatar-height = "{{ $avatarHeight }}"
    >
    </avatar-edit>
@endsection
