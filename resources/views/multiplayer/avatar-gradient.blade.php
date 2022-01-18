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
        :gradients="{{ json_encode($gradients) }}"
    >
    </avatar-gradient-viewer>
    <avatar-gradient-creator
        base-preview-url="{{ route('avatar.gradient.previewimage') }}"
    ></avatar-gradient-creator>
@endsection
