@extends('layouts.layout')

@section('title')
    Site Log Viewer
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'route' => 'admin.home', 'label' => 'Admin' ],
        [ 'route' => 'admin.logs', 'label' => 'Site Log Viewer' ],
    ]) }}
@endsection

@section('content')
    <admin-log-viewer
        :dates="{{ json_encode($dates) }}"
    ></admin-log-viewer>
@endsection
