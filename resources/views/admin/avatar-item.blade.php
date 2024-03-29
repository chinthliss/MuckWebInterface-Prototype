@extends('layouts.layout')

@section('title')
    Avatar Items Admin
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'label' => 'Avatar Items (Admin)' ]
    ]) }}
@endsection

@section('content')
    <admin-avatar-item-viewer
        :items="{{ json_encode($items) }}"
        :file-usage="{{ json_encode($fileUsage) }}"
    >
    </admin-avatar-item-viewer>
@endsection
