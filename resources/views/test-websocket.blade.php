@extends('layouts.layout')

@section('title')
    Websocket Test
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'label' => ' Websocket Test' ]
    ]) }}
@endsection

@section('content')
    <test-websocket></test-websocket>
@endsection
