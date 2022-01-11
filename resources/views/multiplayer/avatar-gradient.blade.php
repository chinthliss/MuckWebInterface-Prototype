@extends('layouts.layout')

@section('title')
    Avatar Gradients
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'label' => 'Avatar Gradients' ]
    ]) }}
@endsection

@section('content')
    <avatar-gradient-viewer
    >
    </avatar-gradient-viewer>
@endsection
