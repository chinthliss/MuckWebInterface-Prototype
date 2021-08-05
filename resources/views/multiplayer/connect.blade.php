@extends('layouts.layout')

@section('title')
    Connect
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'route' => 'multiplayer.home', 'label' => 'Multiplayer' ],
        [ 'label' => 'Connect' ]
    ]) }}
@endsection

@section('content')
    TBC
@endsection
