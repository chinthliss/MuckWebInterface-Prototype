@extends('layouts.layout')

@section('title')
    Style Guide
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ],
        [ 'route' => 'styleguide', 'label' => ' Style Guide' ]
    ]) }}
@endsection

@section('content')
    <styleguide></styleguide>
@endsection
