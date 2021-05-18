@extends('layouts.layout')

@section('title')
    Character Dashboard
@endsection

@section('breadcrumbs')
    {{ Breadcrumbs::render([
        [ 'route' => 'home', 'label' => 'Home' ]
    ]) }}
@endsection

@section('content')
    <character-dashboard
        :characters="{{ json_encode($characters) }}"
    ></character-dashboard>
@endsection
