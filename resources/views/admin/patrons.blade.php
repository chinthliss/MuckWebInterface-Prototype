@extends('layouts.layout')

@section('title')
    Patreon Supporter Browser
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'route' => 'admin.home', 'label' => 'Admin' ],
        [ 'route' => 'admin.patrons', 'label' => 'Patreon Supporter Browser' ],
    ]) }}
@endsection

@section('content')
    <admin-patreon-browser
        api-url="{{ $apiUrl }}"
    ></admin-patreon-browser>
@endsection
