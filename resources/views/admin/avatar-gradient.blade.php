@extends('layouts.layout')

@section('title')
    Avatar Gradients Admin
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'route' => 'multiplayer.avatar.gradients', 'label' => 'Avatar Gradients' ],
        [ 'label' => 'Avatar Gradients (Admin)' ]
    ]) }}
@endsection

@section('content')
    <avatar-gradient-viewer
        :admin="{{ 'true' }}"
        :gradients="{{ json_encode($gradients) }}"
    >
    </avatar-gradient-viewer>
@endsection
